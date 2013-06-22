<?php namespace Dotink\Inkwell
{
	use App;
	use Dotink\Flourish;
	use Dotink\Interfaces;

	/**
	 * The inKWell Scaffolder
	 *
	 * The scaffolder class is a lightweight "templating" class designed to allow you to template
	 * php easily and without some of the normal pitfalls associated with templating PHP with PHP.
	 * Additionally, it has a few helper methods for cleaning up variables and validating variable
	 * names as well as the primary make and build methods.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 * @copyright Copyright (c) 2012, Matthew J. Sahagian
	 * @license Please reference the LICENSE.txt file at the root of this distribution
	 *
	 * @package Dotink\Inkwell
	 */
	class Scaffolder implements Interfaces\Inkwell
	{
		const OPEN_TOKEN             = '_##OPEN-PHP-TAG##_';
		const DEFAULT_ROOT_DIRECTORY = 'user/scaffolding';
		const OPERATION_ROOT         = 'operations';


		/**
		 *
		 */
		static private $app = NULL;


		/**
		 *
		 */
		static private $autoScaffoldClasses = array();


		/**
		 *
		 */
		static private $buildScope = NULL;


		/**
		 *
		 */
		static private $defaultScope = array();


		/**
		 *
		 */
		static private $instances = array();


		/**
		 *
		 */
		static private $root = NULL;


		/**
		 *
		 */
		private $data = array();


		/**
		 *
		 */
		private $template = NULL;


		/**
		 *
		 */
		private $templatePath = NULL;


		/**
		 *
		 */
		private $code = NULL;


		/**
		 *
		 */
		private $namespace = NULL;


		/**
		 *
		 */
		private $outputFile = NULL;


		/**
		 *
		 */
		private $target = NULL;


		/**
		 *
		 */
		private $scope = NULL;


		/**
		 *
		 */
		private $shortName = NULL;


		/**
		 * Initialize the class
		 *
		 * @static
		 * @access public
		 * @param Dotink\Inkwell\IW $app The application instance loading the class
		 * @param array $config The configuration array for the class
		 * @return boolean TRUE on success, FALSE on failure
		 */
		static public function __init($app, Array $config = array())
		{
			self::$app  = $app;
			self::$root = $app->getRoot(__CLASS__, self::DEFAULT_ROOT_DIRECTORY);

			foreach ($app['config']->getByType('array', 'Library') as $eid => $library_config) {
				if (!empty($library_config['auto_scaffold'])) {
					self::$autoScaffoldClasses[] = $library_config['class'];
				}
			}

			if (isset($config['scope']) && is_array($config['scope'])) {
				self::$defaultScope = $config['scope'];
			}
		}


		/**
		 *
		 */
		static public function loadClass($target)
		{
			foreach (self::$autoScaffoldClasses as $make_class) {
				$test = [$make_class, IW::MATCH_METHOD];
				$make = [$make_class, IW::MAKE_METHOD];

				if (is_callable($test) && is_callable($make)) {
					if (call_user_func($test, $target)) {

						$scope = array_merge(self::$defaultScope, [
							'vendor' => NULL,
							'module' => NULL
						]);

						$scaffolder = new self($scope, $target);

						if (call_user_func($make, $scaffolder)) {
							$scaffolder->levy();

							return self::$app->initializeClass($target);
						}
					}
				}
			}

			return FALSE;
		}


		/**
		 *
		 */
		static public function run($operation)
		{
			$operation_file = implode(DS, array(
				self::$root,
				self::OPERATION_ROOT,
				str_replace('\\', '/', $operation) . '.php'
			));

			if (file_exists($operation_file)) {
				include $operation_file;
			}
		}


		/**
		 *
		 */
		static private function scope($scope, Callable $tasks)
		{
			self::$instances  = array();
			self::$buildScope = array_merge(self::$defaultScope, $scope);

			if (call_user_func($tasks, self::$app)) {

				$files_written = array();

				try {
					foreach (self::$instances as $instance) {
						$files_written[] = $instance->write();
					}
				} catch (Flourish\UnexpectedException $e) {
					foreach ($files_written as $file) {
						@unlink($file);
					}

					throw $e;
				}
			}

			self::$instances  = array();
			self::$buildScope = NULL;
		}


		/**
		 *
		 */
		static private function build($class, $target, $data = array())
		{
			if (!self::$buildScope) {
				throw new Flourish\ProgrammerException (
					'Cannot build outside of scaffolder scope'
				);
			}

			$make_method  = [$class, IW::MAKE_METHOD];
			$build_method = [$class, IW::BUILD_METHOD];

			if (!is_callable($make_method) || !is_callable($build_method)) {
				throw new Flourish\ProgrammerException(
					'Cannot build %s, scaffolding is not supported by %s',
					$target,
					$class
				);
			}

			$scaffolder = new self(self::$buildScope, $target);

			if (call_user_func($make_method, $scaffolder, $data)) {
				if (call_user_func($build_method, $scaffolder, $data)) {
					self::$instances[] = $scaffolder;

				} else {
					//
					// Generate notice that build failed
					//
				}

			} else {
				//
				// Generate notice that make failed
				//
			}
		}


		/**
		 *
		 */
		public function __construct(Array $scope, $target)
		{
			$target_parts    = explode('\\', $target);
			$this->shortName = array_pop($target_parts);
			$this->namespace = implode('\\', $target_parts);

			if (isset($scope['module'])) {
				$this->namespace = $scope['module'] . '\\' . $this->namespace;
			}

			if (isset($scope['vendor'])) {
				$this->namespace = $scope['vendor'] . '\\' . $this->namespace;
			}

			$this->namespace = trim($this->namespace, '\\');
			$this->scope     = array_merge(self::$defaultScope, $scope);
			$this->target    = $target;
		}


		/**
		 *
		 */
		public function get($key)
		{
			return isset($this->data[$key])
				? $this->data[$key]
				: NULL;
		}


		public function getClass()
		{
			$namespace = $this->getNamespace();

			return $namespace
				? $namespace . '\\' . $this->getShortName()
				: $this->getShortName();
		}

		/**
		 *
		 */
		public function getNamespace()
		{
			return $this->namespace;
		}


		/**
		 *
		 */
		public function getScope($key)
		{
			return isset($this->scope[$key])
				? $this->scope[$key]
				: NULL;
		}


		/**
		 *
		 */
		public function getShortName()
		{
			return $this->shortName;
		}


		/**
		 *
		 */
		public function getTarget()
		{
			return $this->target;
		}


		/**
		 *
		 */
		public function make(Array $data = array())
		{
			$this->data     = $data;
			$this->template = @file_get_contents($this->templatePath);

			if (!$this->template) {
				return FALSE;
			}

			if (strpos($this->template, '<?php') !== FALSE) {
				$this->template = str_replace('<?php', self::OPEN_TOKEN, $this->template);
			}

			$this->template = preg_replace('#\%\>(\r\n|\n|\r)#', '%>$1$1', $this->template);
			$this->code     = call_user_func(function(){
				ob_start();
				eval('?>' . $this->template);
				return str_replace(self::OPEN_TOKEN, '<?php', ob_get_clean());
			});

			return TRUE;
		}


		/**
		 *
		 */
		public function setTemplate($template_path)
		{
			$this->templatePath = self::$root . DS . $template_path . '.php';

			return $this;
		}


		/**
		 *
		 */
		public function setOutputFile($output_file)
		{
			$this->outputFile = $output_file;

			return $this;
		}


		/**
		 *
		 */
		private function inject($tabs, $file, $keep_tags = FALSE)
		{
			$partial = @file_get_contents($file);

			if (!$partial) {
				return FALSE;
			}

			if (strpos($partial, '<?php') !== FALSE) {
				if (!$keep_tags) {
					if (preg_match('#^\<\?php(\s*)(.)#', $partial, $matches)) {
						$partial = str_replace("\n" . ltrim($match[1], "\n"), "\n", $str);
					}

					$partial = trim(str_replace('<?php', '', $partial));
				} else {
					$partial = str_replace('<?php', self::OPEN_TOKEN, $partial);
				}
			}

			$partial = preg_replace('#\%\>(\r\n|\n|\r)#', '%>$1$1', $partial);
			$code    = call_user_func(function() use ($partial) {
				ob_start();
				eval('?>' . $partial);
				return ob_get_clean();
			});

			$tabs = str_pad('', $tabs, "\t");
			$code = $tabs . str_replace("\n", "\n" . $tabs, $code);

			echo $keep_tags
				? str_replace(self::OPEN_TOKEN, '<?php', $code)
				: $code;

			return TRUE;
		}


		/**
		 *
		 */
		private function levy()
		{
			eval('?>' . $this->code);
		}


		/**
		 *
		 */
		private function write()
		{
			if (!$this->outputFile) {
				throw new Flourish\ProgrammerException(
					'Could not write scaffolded code, not output file specified'
				);
			}

			$file = new App\File($this->outputFile);

			$file->write($this->code);
		}
	}
}