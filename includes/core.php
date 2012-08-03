<?php

	/**
	 * IW is the core inKWell class.
	 *
	 * It represents an application context and is the starting point for serving a request.
	 *
	 * @copyright Copyright (c) 2012, Matthew J. Sahagian
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 *
	 * @license Please reference the LICENSE.txt file at the root of this distribution
	 *
	 * @package Dotink\Inkwell
	 */

	namespace Dotink\Inkwell;

	use Dotink\Flourish;

	class IW implements \ArrayAccess
	{
		const LB                       = PHP_EOL;
		const DS                       = DIRECTORY_SEPARATOR;

		const INITIALIZATION_METHOD    = '__init';
		const MATCH_CLASS_METHOD       = '__match';

		const DEFAULT_CONFIG_DIRECTORY = 'config';
		const DEFAULT_WRITE_DIRECTORY  = 'assets';
		const DEFAULT_EXECUTION_MODE   = 'development';

		const REGEX_ABSOLUTE_PATH      = '#^(/|\\\\|[a-z]:(\\\\|/)|\\\\|//)#i';



		/**
		 * Child objects of the application; accessible via array access
		 *
		 * @access private
		 * @var array
		 */
		private $children = array();


		/**
		 * Autoloader mappings
		 *
		 * @access private
		 * @var array
		 */
		private $loaders = array();


		/**
		 * Registered autoloader standard callbacks
		 *
		 * @access private
		 * @var array
		 */
		private $loaderStandards = array();


		/**
		 * Normalized root directories
		 *
		 * @access private
		 * @var array
		 */
		private $roots = array();


		/**
		 * The base write directory
		 *
		 * @access private
		 * @var array
		 */
		private $writeDirectory = NULL;


		/**
		 * Initializes a new inKWell application
		 *
		 * @static
		 * @access public
		 * @param string $root_directory The root directory for the application
		 * @param Callable $config_callback The configuration callback
		 * @return IW A new instance of an inKWell application
		 */
		static public function init($root_directory, Callable $config_callback)
		{
			//
			// Add some basic definitions
			//

			if (!@constant('DS')) {
				define('DS', self::DS);
			}

			if (!@constant('LB')) {
				define('LB', self::LB);
			}

			if (!@constant('REGEX_ABSOLUTE_PATH')) {
				define('REGEX_ABSOLUTE_PATH', self::REGEX_ABSOLUTE_PATH);
			}

			$app = new self($root_directory, $config_callback);

			if (!isset($app['config'])) {

				//
				// Return our application immediately if there is no config
				//

				return $app;
			}

			$config = $app['config']->get('array', 'inkwell');

			//
			// Initialize Date and Time Information, this has to be before any
			// time related functions.
			//

			Flourish\Timestamp::setDefaultTimezone(isset($config['default_timezone'])
				? $config['default_timezone']
				: 'GMT'
			);

			if (isset($config['date_formats']) && is_array($config['date_formats'])) {
				foreach ($config['date_formats'] as $name => $format) {
					Flourish\Timestamp::defineFormat($name, $format);
				}
			}

			//
			// Set up execution mode
			//

			$valid_execution_modes = ['development', 'production'];
			$app->executionMode    = self::DEFAULT_EXECUTION_MODE;

			if (isset($config['execution_mode'])) {
				if (in_array($config['execution_mode'], $valid_execution_modes)) {
					$app->executionMode = $config['execution_mode'];
				}
			}

			//
			// Initialize Error Reporting
			//

			if (isset($config['error_level'])) {
				error_reporting($config['error_level']);
			}

			if (isset($config['display_errors'])) {
				if ($config['display_errors']) {
					Flourish\Core::enableErrorHandling('html');
					Flourish\Core::enableExceptionHandling('html', 'time');
					ini_set('display_errors', 1);
				} elseif (isset($config['error_email_to'])) {
					Flourish\Core::enableErrorHandling($config['error_email_to']);
					Flourish\Core::enableExceptionHandling($config['error_email_to'], 'time');
					ini_set('display_errors', 0);
				} else {
					ini_set('display_errors', 0);
				}
			} elseif ($app->checkExecutionMode('development')) {
				ini_set('display_errors', 1);
			} else {
				ini_set('display_errors', 0);
			}

			//
			// Set up our write directory
			//

			$write_directory = !isset($config['write_directory']) || !$config['write_directory']
				? self::DEFAULT_WRITE_DIRECTORY
				: $config['write_directory'];

			if (!preg_match(REGEX_ABSOLUTE_PATH, $write_directory)) {
				$app->writeDirectory = $app->getRoot(NULL, $write_directory);
			} else {
				$app->writeDirectory = $write_directory;
			}

			return $app;
		}


		/**
		 * Transforms a class to the inKWell standard
		 *
		 * @static
		 * @access private
		 * @param string $class the class to transform
		 * @return string The transformed class
		 */
		static private function transformClassToIW($class)
		{
			$class = ltrim($class, '\\');
			$parts = explode('\\', $class);
			$class = array_pop($parts);
			$path  = implode(DS, array_map('Dotink\Flourish\Grammar::underscorize', $parts));

			return $path . DS . $class . '.php';
		}


		/**
		 * Transforms a class to PSR-0 standard
		 *
		 * @static
		 * @access private
		 * @param string $class The class to transform
		 * @return string The transformed class
		 */
		static private function transformClassToPSR0($class)
		{
			$class = ltrim($class, '\\');
			$class = str_replace('\\', DS, $class);
			$class = str_replace('_',  DS, $class);

			return $class . '.php';
		}


		/**
		 * Creates a new inKWell Application.
		 *
		 * The constructor cannot be called directly.  Instead, applications should be generated
		 * using the iw::init() method which runs through initialization process as well.
		 *
		 * @access private
		 * @param string $root_directory The root directory for the application
		 * @param Callable $config_callback The configuration callback
		 * @return void
		 */
		private function __construct($root_directory, Callable $config_callback = NULL)
		{
			//
			// Set our application root
			//

			$this->setRoot(NULL,     $root_directory);
			$this->setRoot('config', $root_directory . DS . self::DEFAULT_CONFIG_DIRECTORY);

			//
			// Our initial loader map is established.  This will use compatibility transformations,
			// meaning that namespaces will be ignored when loading the classes.
			//

			$this->loaders['Dotink\Flourish\*'] = 'includes/lib/flourish';
			$this->loaders['Dotink\Inkwell\*']  = 'includes/lib';

			spl_autoload_register([$this, 'loadClass']);

			$this->registerLoaderStandard('PSR0', [__CLASS__, 'transformClassToPSR0']);
			$this->registerLoaderStandard('IW',   [__CLASS__, 'transformClassToIW']);

			//
			// Get our config from our callback
			//

			if ($config_callback) {
				$this->children['config'] = call_user_func($config_callback, $this);

				//
				// Merge in additional autoloaders
				//

				$this->loaders = array_merge(
					$this->loaders,
					$this['config']->get('array', 'autoloaders')
				);
			}
		}


		/**
		 * Checks whether or not the app is in a certain execution mode
		 *
		 * @access public
		 * @param string $execution_mode The execution mode to check against
		 * @return boolean TRUE if the app is in the provided execution mode, FALSE otherwise
		 */
		public function checkExecutionMode($execution_mode)
		{
			return $this->executionMode == $execution_mode;
		}


		/**
		 * Gets a configured root directory from the configured root directories
		 *
		 * @access public
		 * @param string $element The class or configuration element
		 * @param string $default A default root, relative to the application root
		 * @return string A reference to the root directory for "live roots"
		 */
		public function getRoot($element = NULL, $default = NULL)
		{
			$element = !$element ? NULL : strtolower($element);

			if (!isset($this->roots[$element])) {
				if (!$default) {
					$directory = $this->roots[NULL];
				} else {
					$default   = str_replace('/', DS, rtrim($default, '/\\' . DS));
					$directory = !preg_match(REGEX_ABSOLUTE_PATH, $default)
						? $this->roots[NULL] . DS . $directory
						: $default;
				}
			} else {
				$directory = $this->roots[$element];
			}

			return $directory;
		}


		/**
		 * Gets a write directory.
		 *
		 * If the optional parameter is entered it will attempt to get it as a sub directory of
		 * the overall write directory.  If the sub directory does not exist, it will create it
		 * with owner and group writable permissions.
		 *
		 * @static
		 * @access public
		 * @param string $sub_directory The optional sub directory to return.
		 * @return string The writable directory
		 */
 		public function getWriteDirectory($sub_directory = NULL)
		{
			if ($sub_directory) {
				$sub_directory   = str_replace('/', DS, $sub_directory);
				$write_directory = !preg_match(REGEX_ABSOLUTE_PATH, $sub_directory)
					? self::getWriteDirectory() . DS . $sub_directory
					: $sub_directory;
			} else {
				$write_directory = $this->$writeDirectory;
			}

			if (!is_dir($write_directory)) {
				Flourish\Directory::create($write_directory);
			}

			return rtrim($write_directory, '/\\' . iw::DS);
		}


		/**
		 * Loads a class using configured loaders
		 *
		 * @access public
		 * @param string $class The class to load
		 * @param array $loaders An autoloader map to use
		 * @return boolean TRUE if the class was loaded and initialized, FALSE otherwise
		 */
		public function loadClass($class, Array $loaders = array())
		{
			if (!count($loaders)) {
				$loaders = $this->loaders;
			}

			foreach ($loaders as $test => $target) {
				if (strpos($test, '*') !== FALSE) {
					$regex = str_replace('*', '(.*?)', str_replace('\\', '\\\\', $test));
					$match = preg_match('/^' . $regex . '$/', $class);
				} elseif (class_exists($test)) {
					$test  = [$test, self::MATCH_CLASS_METHOD];
					$match = is_callable($test) ? call_user_func($test, $class) : FALSE;
				} else {
					$match = TRUE;
				}

				if (class_exists($class, FALSE)) {

					//
					// Recursion may have loaded the class at this point, so we may not need to go
					// any further.
					//

					return TRUE;

				} elseif ($match) {

					$target = explode(':', $target, 2);

					if (count($target) == 1) {
						$standard = NULL;
						$target   = trim($target[0]);
					} else {
						$standard = trim($target[0]);
						$target   = trim($target[1]);
					}

					//
					// But maybe we do...
					//

					$file = implode(DS, array(
						$this->getRoot(),

						//
						// Trim leading or trailing directory separators from target
						//

						trim($target, '/\\' . DS),

						//
						// Replace any backslashes in the class with directory separator
						// to support Namespaces and trim the leading root namespace if present.
						//

						$this->transformClass($standard, $class)
					));

					if (file_exists($file)) {

						include $file;

						/*
						if (is_array($interfaces = class_implements($class, FALSE))) {
							return (in_array('inkwell', $interfaces))
								? self::initializeClass($class)
								: TRUE;
						}
						*/
					}
				}
			}

			return FALSE;
		}

		/**
		 * Sets a child element via array access (NOT ALLOWED)
		 *
		 * @access public
		 * @param mixed $offset The child element offset to set
		 * @param mixed $value The value to set for the offset
		 * @return void
		 */
		public function offsetSet($offset, $value)
		{
			throw new Flourish\ProgrammerException(
				'Cannot set child "%s", access denied',
				$offset
			);
		}

		/**
		 * Checks whether or not a child element exists
		 *
		 * @access public
		 * @param mixed $offset The child element offset to check for existence
		 * @return boolean TRUE if the child exists, FALSE otherwise
		 */
		public function offsetExists($offset)
		{
			return isset($this->children[$offset]);
		}

		/**
		 * Attempts to unset a child element (NOT ALLOWED)
		 *
		 * @access public
		 * @param mixed $offset The child element offset to unset
		 * @return void
		 */
		public function offsetUnset($offset)
		{
			throw new Flourish\ProgrammerException(
				'Cannot unset child "%s", access denied',
				$offset
			);
		}

		/**
		 * Gets a child element
		 *
		 * @access public
		 * @param mixed $offset The child element offset to get
		 * @return void
		 */
		public function offsetGet($offset) {
			return $this->children[$offset];
		}

		/**
		 * Registers an autoloader standard
		 *
		 * @access public
		 * @param string $standard The standard to register as
		 * @param Callable $transform_callback The callback to register for transformation
		 * @return void
		 */
		public function registerLoaderStandard($standard, Callable $transform_callback)
		{
			$this->loaderStandards[strtolower($standard)] = $transform_callback;
		}

		/**
		 * Runs the Application with a provided Router and Request
		 *
		 * @access public
		 * @param Routes $routes
		 * @param Request $request
		 * @return integer The return value
		 */
		public function run($routes, $request)
		{
			foreach ($this['config']->get('array', 'routes') as $route => $target) {
				$routes->any[$route] = $target;
			}

			$this->children['routes']  = $routes;
			$this->children['request'] = $request;

			return $this['routes']->run($this['request']);
		}

		/**
		 * Sets a Root Directory
		 *
		 * @access protected
		 * @param string $key The key to set a root directory for
		 * @param string $directory The root directory
		 * @return void
		 */
		protected function setRoot($key, $directory)
		{
			$directory         = str_replace('/', DS, rtrim($directory, '/\\' . DS));
			$this->roots[$key] = !preg_match(REGEX_ABSOLUTE_PATH, $directory)
				? realpath($this->roots[NULL] . DS . $directory)
				: realpath($directory);

			if (!is_dir($this->roots[$key])) {
				throw new Flourish\ProgrammerException(
					'Cannot set root directory "%s", directory does not exist',
					$directory
				);
			}
		}

		/**
		 * Transforms a class name to a given (registered) standard
		 *
		 * @access private
		 * @param string $standard The standard to use (case insensitive)
		 * @param string $class The class to transform
		 * @return string The transformed class to file according to the standard
		 */
		private function transformClass($standard, $class)
		{
			//
			// This is our compatibility standard.  It ignores namespaces altogether
			//
			if ($standard == NULL) {
				$class = ltrim($class, '\\');
				$parts = explode('\\', $class);

				return array_pop($parts) . '.php';
			}

			$standard = strtolower($standard);

			if (!isset($this->loaderStandards[$standard])) {
				var_dump($this->loaderStandards); exit();
				throw new Flourish\ProgrammerException(
					'Cannot transform class using "%s", standard not registered',
					$standard
				);
			}

			return call_user_func($this->loaderStandards[$standard], $class);
		}
	}