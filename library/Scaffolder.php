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
			//
			// Convert namespace separator to \
			// - see if file exists in scaffolder_root/operations
			// - if no, throw error (unknown operation)
			// - if yes, run it
			//
		}


		/**
		 *
		 */
		static private function scope($scope, Callable $tasks)
		{
			self::$buildScope = array_merge(self::$defaultScope, $scope);

			if (call_user_func($tasks, self::$app)) {
				foreach (self::$instances as $instance) {
					$instance->write();
				}
			}

			self::$instances  = array();
			self::$buildScope = NULL;
		}


		/**
		 *
		 */
		static private function build($make_class, $target)
		{
			if (!self::$buildScope) {
				throw new Flourish\ProgrammerException (
					'Cannot build outside of scaffolder scope'
				);
			}

			$make = [$make_class, IW::MAKE_METHOD];

			if (!is_callable($make)) {
				throw new Flourish\ProgrammerException(
					'Cannot build %s, scaffolding is not supported by %s',
					$target,
					$make_class
				);
			}

			$scaffolder = new self(self::$buildScope, $target);

			if (call_user_func($make, $scaffolder)) {
				self::$instances[] = $scaffolder;

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

			$this->scope  = array_merge(self::$defaultScope, $scope);
			$this->target = $target;
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
			return $this->getNamespace() . '\\' . $this->getShortName();
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

			$this->code = call_user_func(function(){
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
		private function levy()
		{
			eval('?>' . $this->code);
		}


		/**
		 *
		 */
		private function write()
		{

		}
	}
}