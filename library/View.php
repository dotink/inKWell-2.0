<?php namespace Dotink\Inkwell
{
	use App;
	use ArrayObject;
	use Dotink\Flourish;
	use Dotink\Interfaces;
	use Assetic\Asset;

	/**
	 * View Class
	 *
	 * @copyright Copyright (c) 2012, Matthew J. Sahagian
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 *
	 * @license Please reference the LICENSE.txt file at the root of this distribution
	 *
	 * @package Dotink\Inkwell
	 */
	class View extends ArrayObject implements Interfaces\Inkwell
	{

		const DEFAULT_TEMPLATE = 'main';


		/**
		 * The app instance responsible for this view
		 *
		 * @static
		 * @access private
		 * @var Dotink\Inkwell\IW
		 */
		static private $app = NULL;


		/**
		 * The default view root directory
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $defaultRoot = NULL;


		/**
		 * Cache directory relative to $_SERVER['DOCUMENT_ROOT']
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $cacheDirectory = 'cache';


		/**
		 * The cache mode (matches inkwell's execution mode)
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $cacheMode = EXEC_MODE_DEVELOPMENT;


		/**
		 * A lists of asset filters keyed by extension
		 *
		 * This supports css and js by default with no filters for compatibility.
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $assetFilters = [
			'css' => [],
			'js'  => []
		];


		/**
		 * A map of asset extensions to their compiled extensions
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $extensionMap = [
			'coffee' => 'js',
			'css'    => 'css',
			'dart'   => 'js',
			'js'     => 'js',
			'less'   => 'css',
			'scss'   => 'css',
			'ts'     => 'js'
		];


		/**
		 * The directory containing our helpers, relative to the application root
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $helperDirectory = NULL;


		/**
		 * Runtime list of loaded helpers
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $helpers = array();


		/**
		 * Registered view template/root combinations
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $registeredViews = array();


		/**
		 * Assets added to the view
		 *
		 * @access private
		 * @var array
		 */
		private $assets = array();


		/**
		 * The emitters stack for looping with repeat()
		 *
		 * @access private
		 * @var array
		 */
		private $emitters = array();


		/**
		 * A shared view merged with subviews during rendering
		 *
		 * @access private
		 * @var View
		 */
		private $head = NULL;


		/**
		 * Components (subviews, templates, etc) added to the view
		 *
		 * @access private
		 * @var array
		 */
		private $components = array();


		/**
		 * The current file being rendered
		 *
		 * @access private
		 * @var string
		 */
		private $currentFile = NULL;


		/**
		 * Data added to the view
		 *
		 * @access private
		 * @var array
		 */
		private $data = array();


		/**
		 *
		 */
		private $parent = NULL;


		/**
		 * The root directory for this view element's templates
		 *
		 * @access private
		 * @var string
		 */
		private $rootDirectory = NULL;


		/**
		 * The primary template for this view
		 *
		 * @access private
		 * @var string
		 */
		private $template = NULL;


		/**
		 * The type of view this is, based on the template extension
		 *
		 * @access private
		 * @var string
		 */
		private $type = NULL;


		/**
		 *
		 */
		private $views = array();


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
			self::$defaultRoot = $app->getRoot(__CLASS__);

			if (isset($config['helper_directory'])) {
				self::$helperDirectory = $app->getRoot(NULL, $config['helper_directory']);
			}

			if (isset($config['cache_directory'])) {
				self::$cacheDirectory = $config['cache_directory'];
			}

			if (isset($config['extension_map']) && is_array($config['extension_map'])) {
				self::$extensionMap = array_merge(
					self::$extensionMap,
					$config['extension_map']
				);
			}

			if (isset($config['asset_filters']) && is_array($config['asset_filters'])) {
				self::$assetFilters = array_merge_recursive(
					self::$assetFilters,
					$config['asset_filters']
				);
			}

			self::$app = $app;
		}


		/**
		 *
		 */
		static public function register($alias, $template, $root_directory)
		{
			self::$registeredViews[$alias] = [
				'file' => $template,
				'root' => $root_directory
			];
		}


		/**
		 *
		 */
		static public function create($view, array $components = array(), array $data = array())
		{
			if (strpos($view, '.') === FALSE) {
				if (isset(self::$registeredViews[$view])) {
					$template       = self::$registeredViews[$view]['file'];
					$root_directory = self::$registeredViews[$view]['root'];
				} else {
					$template       = self::DEFAULT_TEMPLATE . '.' . $view;
					$root_directory = self::$defaultRoot;
				}
			} else {
				$template       = $view;
				$root_directory = NULL;
			}

			$view = new self($template, $root_directory);

			return $view($components, $data);
		}


		/**
		 *
		 */
		public function __construct($view, $root = NULL)
		{
			if (is_object($view) && $view instanceof self) {
				$this->parent = $view;
				$this->head   = $this;
				$this->type   = $this->parent->type;

			} else {
				if (count($file_parts = explode('.', $view)) != 2) {
					throw new Flourish\ProgrammerException(
						'Invalid view %s specified',
						$view
					);
				}

				$this->template      = $file_parts[0];
				$this->type          = $file_parts[1];
				$this->rootDirectory = $root;

				if (!isset(self::$helpers[$this->type])) {
					$helper_file = self::$helperDirectory . DS . $this->type . '.php';

					if (file_exists($helper_file)) {
						include($helper_file);

						self::$helpers[$this->type] = TRUE;

					} else {
						self::$helpers[$this->type] = FALSE;
					}
				}
			}
		}


		/**
		 *
		 */
		public function __invoke(array $components, array $data = array())
		{
			foreach ($components as $element => $view) {
				if (isset($this->components[$element])) {
					unset($this->components[$element]);
					unset($this->views[$element]);
				}

				$this->add($element, $view);
			}

			$this->exchangeArray($data);

			return $this;
		}


		/**
		 *
		 */
		public function bind($parent, $component, $action = NULL)
		{
			$parent = self::create($parent, [$component => $this]);

			$action->setContext(array_merge(
				$this->context,
				[
					'view' => $parent
				]
			));

			return $parent;
		}


		/**
		 *
		 */
		public function add($element, $view)
		{
			if (!isset($this->components[$element])) {
				$this->components[$element] = array();
				$this->views[$element]      = array();
			}

			if (is_array($view)) {
				$this->components[$element] = array_merge(
					$this->components[$element],
					$view
				);

			} else {
				$this->components[$element][] = $view;
			}
		}


		/**
		 *
		 */
		public function asset($element, $asset)
		{
			if (!isset($this->assets[$element])) {
				$this->assets[$element] = array();
			}

			if (!isset($this->assets[$element][$this->currentFile])) {
				$this->assets[$element][$this->currentFile] = array();
			}

			if (!preg_match('#^http(s?)://(.*)$#', $asset)) {
				$asset = !preg_match(REGEX\ABSOLUTE_PATH, $asset)
					? $_SERVER['DOCUMENT_ROOT'] . DS . ltrim($asset, '\\/' . DS)
					: $asset;
			}

			$this->assets[$element][$this->currentFile][] = $asset;
		}


		/**
		 *
		 */
		public function each($element, $emitter)
		{
			if (!$this->has($element)) {
				return;
			}

			$this->emitters[] = $emitter;

			if (is_array($this[$element])) {

				foreach ($this[$element] as $i => $value) {
					$emitter($value, $i);
				}

			} else {
				$emitter($this[$element], 0);
			}

			array_pop($this->emitters);
		}


		/**
		 *
		 */
		public function has($element)
		{
			return array_key_exists($element, $this);
		}


		/**
		 *
		 */
		public function join($element, $separator = '::')
		{
			if (!$this->has($element)) {
				return;
			}

			return is_array($this[$element])
				? implode($separator, $this->offsetGet($element))
				: (string) $this[$element];
		}


		public function compile()
		{
			if (!isset($this->rootDirectory) && isset($this->parent)) {
				$this->rootDirectory = $this->parent->rootDirectory;
			}

			reset($this->components);

			while ($element = key($this->components)) {
				$list = $this->components[$element];

				foreach ($list as $i => $view) {
					if (is_string($view)) {
						$this->setCurrentFile(
							!preg_match(REGEX\ABSOLUTE_PATH, $view)
								? $this->rootDirectory . DS . $view . '.php'
								: $view
						);

						$this->views[$element][$i] = $this->buffer(function($app) {
							include $this->currentFile;
						});

					} elseif (is_object($view) && $view instanceof self) {
						$view->parent              = $this;
						$view->head                = $this->head;
						$this->views[$element][$i] = $view->make();

					} else {
						throw new Flourish\ProgrammerException(
							'Invalid component assigned to element %s',
							$element
						);
					}
				}

				next($this->components);
			}
		}


		/**
		 * Compiles this view
		 *
		 * @access public
		 * @return string The view compiled to a string
		 */
		public function make()
		{
			if (!$this->head) {
				$this->head = new self($this);
			}

			$this->compile();

			if (!$this->parent) {
				$this->head->compile();
			}

			$this->setCurrentFile(!
				preg_match(REGEX\ABSOLUTE_PATH, $this->template)
					? $this->rootDirectory . DS . $this->template . '.' . $this->type . '.php'
					: $this->template . '.' . $this->type . '.php'
			);

			$view = $this->buffer(function($app) {
				include $this->currentFile;
			});

			$this->head = NULL;

			return $view;
		}


		/**
		 *
		 */
		public function offsetGet($offset)
		{
			//
			// We overload this so we can return null for easy defaults such as the following:
			// $this['element'] ?: 'Default'
			//

			return isset($this[$offset])
				? parent::offsetGet($offset)
				: NULL;
		}


		/**
		 *
		 */
		public function pack($element, $value = NULL)
		{
			if ($value === NULL && is_array($element)) {
				$this->exchangeArray(array_merge($this, $element));
			} else {
				if (is_string($element)) {
					$this->offsetSet($element, $value);
				} else {
					throw new Flourish\ProgrammerException(
						'Cannot set element with non-string reference of type %s',
						gettype($element)
					);
				}
			}

			return $this;
		}


		/**
		 *
		 */
		public function peal()
		{
			if (!isset($this[$element])) {
				return NULL;
			}

			return is_array($this[$element])
				? array_pop($this[$element])
				: $this[$element];
		}


		/**
		 *
		 */
		public function pull($element)
		{
			if (!isset($this[$element])) {
				return NULL;
			}

			return is_array($this[$element])
				? array_shift($this[$element])
				: $this[$element];
		}


		/**
		 *
		 */
		public function push($element, $value)
		{
			if (!isset($this[$element])) {
				$this->offsetSet($element, []);
			}

			if (!is_array($this[$element])) {
				$this->offsetSet($element, [$this[$element]]);
			}

			$stack   = $this->offsetGet($element);
			$stack[] = $value;

			$this->offsetSet($element, $stack);

			return $this;
		}


		/**
		 * Repeat the last emitter using a traversable
		 *
		 * @
		 */
		public function repeat($traversable)
		{
			if (!count($this->emitters)) {
				return;
			}

			$emitter = $this->emitters[count($this->emitters) - 1];

			foreach ($traversable as $i => $value) {
				$emitter($value, $i);
			}
		}


		/**
		 *
		 */
		private function buffer(\Closure $closure)
		{
			if (ob_start()) {
				$closure(self::$app);
				return ob_get_clean();
			}

			throw new Flourish\EnvironmentException(
				'Failed to start output buffering'
			);
		}


		/**
		 *
		 */
		private function buildAssetCache($cache_file, $files)
		{
			$assets = [];

			foreach ($files as $file) {
				$extension = pathinfo($file, PATHINFO_EXTENSION);
				$filters   = $this->getAssetFilters($extension);
				$assets[]  = preg_match('#^http(s?)://(.*)$#', $file)
					? new Asset\HttpAsset($file, $filters)
					: new Asset\FileAsset($file, $filters);
			}

			$collection = new Asset\AssetCollection($assets);

			if (!file_put_contents($cache_file, $collection->dump())) {
				throw new Flourish\EnvironmentException(
					'Could not write to asset cache file %s',
					$cache_file
				);
			}
		}


		/**
		 *
		 */
		private function getAssetsByType($element)
		{
			$assets_by_type = array();

			foreach (array_reverse(array_keys($this->assets[$element])) as $file) {
				foreach ($this->assets[$element][$file] as $asset) {
					$extension = pathinfo($asset, PATHINFO_EXTENSION);

					if (!isset(self::$extensionMap[$extension])) {
						throw new Flourish\ProgrammerException(
							'Unsupported asset %s with type %s',
							$asset,
							$extension
						);
					}

					$asset_type = self::$extensionMap[$extension];

					if (!isset($assets_by_type[$asset_type])) {
						$assets_by_type[$asset_type] = array();
					}

					$assets_by_type[$asset_type][] = $asset;
				}
			}

			return $assets_by_type;
		}


		/**
		 *
		 */
		private function getAssetFilters($extension)
		{
			$filters = array();

			if (isset(self::$assetFilters[$extension])) {
				foreach (self::$assetFilters[$extension] as $filter_class) {
					$filters[] = new $filter_class();
				}
			}

			return $filters;
		}


		/**
		 *
		 */
		private function getAssetRebuildRequirement($cache_file, $files)
		{
			$rebuild = FALSE;

			if (!file_exists($cache_file)) {
				$rebuild = TRUE;

			} elseif (self::$cacheMode == EXEC_MODE_DEVELOPMENT) {
				$cache_mtime = @filemtime($cache_file);

				foreach ($files as $file) {
					$file_mtime = @filemtime($file);

					if ($file_mtime > $cache_mtime) {
						$rebuild = TRUE;
						break;
					}
				}
			}

			return $rebuild;
		}


		/**
		 *
		 */
		private function place($element, $preprocess = TRUE)
		{
			//
			// Cycle through assets under this name
			//

			if (isset($this->assets[$element])) {

				$assets_by_type = $this->getAssetsByType($element);

				if ($preprocess) {
					foreach ($assets_by_type as $type => $files) {
						$cache_key  = md5(implode('::', $files));
						$cache_file = implode(DS, [
							$_SERVER['DOCUMENT_ROOT'],
							self::$cacheDirectory,
							$cache_key . '.' . $type
						]);

						if ($this->getAssetRebuildRequirement($cache_file, $files)) {
							$this->buildAssetCache($cache_file, $files);
						}

						$assets_by_type[$type] = [$cache_file];
					}
				}

				foreach ($assets_by_type as $type => $files) {
					switch ($this->type . '::' . $type) {
						case 'html::css':
							$template = '<link rel="stylesheet" type="text/css" href="%s" />';
							break;
						case 'html::js':
							$template = '<script type="text/javascript" src="%s"></script>';
							break;
						default:
							$template = NULL;
							break;
					}

					if ($template) {
						foreach ($files as $file) {
							echo sprintf($template, $this->translateWeb($file)) . PHP_EOL;
						}
					}
				}

			}

			if (isset($this->views[$element])) {
				echo implode(PHP_EOL, $this->views[$element]);
			}
		}


		/**
		 *
		 */
		private function setCurrentFile($file)
		{
			$this->currentFile       = $file;
			$this->head->currentFile = $file;
		}


		/**
		 *
		 */
		private function translateWeb($file)
		{
			if (!preg_match('#^http(s?)://(.*)$#', $file)) {
				$file = implode('?', [
					str_replace($_SERVER['DOCUMENT_ROOT'], '', $file),
					filemtime($file)
				]);
			}

			return $file;
		}
	}
}