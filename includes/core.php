<?php namespace Dotink\Inkwell {

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

		const ROUTES_INTERFACE         = 'Dotink\Interfaces\Routes';
		const RESPONSE_INTERFACE       = 'Dotink\Interfaces\Response';

		/**
		 * Whether or not an app instance has been created somewhere
		 *
		 * @access private
		 * @var boolean
		 */
		static private $appExists = FALSE;

		/**
		 * Child objects of the application; accessible via array access
		 *
		 * @access private
		 * @var array
		 */
		private $children = array();


		/**
		 * Available factories
		 */
		private $factories = array();


		/**
		 * Classes which we've initialized
		 *
		 * @access private
		 * @var array
		 */
		private $initializedClasses = array();


		/**
		 * Autoloader mappings
		 *
		 * @access private
		 * @var array
		 */
		private $loaders = array();


		/**
		 * Autoloading standard tranformation callbacks
		 *
		 * @access private
		 * @var array
		 */
		private $loadingStandards = array();


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
		static public function init($root_directory, $library_directory = 'library')
		{
			//
			// Add some basic definitions if another app hasn't already
			//

			if (!self::$appExists) {
				define(__NAMESPACE__ . '\\' . 'DS', self::DS);
				define(__NAMESPACE__ . '\\' . 'LB', self::LB);
				define(__NAMESPACE__ . '\\' . 'REGEX_ABSOLUTE_PATH', self::REGEX_ABSOLUTE_PATH);

				self::$appExists = TRUE;
			}

			return new self($root_directory, $library_directory);
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
			$path  = implode(DS, $parts);
			$path  = (new Flourish\Text($path))->underscorize();

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
		 * @param string $library_directory The inKWell core library directory
		 * @return void
		 */
		private function __construct($root_directory, $library_directory)
		{
			//
			// Set our application root
			//

			$this->setRoot(NULL, $root_directory);

			//
			// Our initial loader map is established.  This will use compatibility transformations,
			// meaning that namespaces will be ignored when loading the classes.
			//

			$this->loaders['Dotink\Flourish\*']   = $library_directory . DS . 'flourish';
			$this->loaders['Dotink\Inkwell\*']    = $library_directory;

			$this->loaders['Dotink\Interfaces\*'] = $library_directory . DS . 'interfaces';
			$this->loaders['Dotink\Traits\*']     = $library_directory . DS . 'traits';

			spl_autoload_register([$this, 'loadClass']);
		}


		/**
		 * Adds an autoloading standard
		 *
		 * @access public
		 * @param string $standard The standard to register as
		 * @param Callable $transform_callback The callback to register for transformation
		 * @return void
		 */
		public function addLoadingStandard($standard, Callable $transform_callback)
		{

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
		 * Create an an instance from the available factories
		 *
		 * @access public
		 * @param string $alias The alias the factory is registered under
		 * @param string|array $interfaces The interfaces it must implement
		 * @param mixed First parameter to factory...
		 * @param ...
		 * @return mixed An object instance of the alias type, implementing the provided interfaces
		 */
		public function create($alias = NULL, $interfaces = [], $param = NULL)
		{
			if ($alias === NULL) {
				return new self($this->getRoot());
			}

			settype($interfaces, 'array');
			$alias = strtolower($alias);

			foreach ($this->factories[$alias] as $class => $factory) {
				if (class_exists($class)) {

					$class_interfaces = class_implements($class);

					if (count(array_diff($interfaces, $class_interfaces))) {
						continue;
					}

					$factory = !is_callable($factory)
						? $class . '::' . $factory
						: $factory;

					if (is_callable($factory)) {
						$result = call_user_func_array($factory, array_slice(func_get_args(), 2));

						if (!($result instanceof $class)) {
							throw new Flourish\ProgrammerException(
								'Fetched instance is not an instance of class "%s", bad factory',
								$class
							);
						}

						return $result;
					}
				}
			}

			throw new Flourish\ValidationException(
				'No class implementing the requested interfaces exists for alias "%s"',
				$alias
			);
		}


		/**
		 * Configure our application
		 *
		 * @access public
		 * @param string $config_name The name of the config to use, default NULL (Config default)
		 * @param string $config_root The configuration root
		 * @return IW The application for chaining
		 */
		public function config($config_name = NULL, $config_root = NULL)
		{
			$this->setRoot('config', !isset($config_root)
				? $this->getRoot() . DS . self::DEFAULT_CONFIG_DIRECTORY
				: $config_root
			);

			$config = $this->create('config', [], $this->getRoot('config'), $config_name);

			//
			// Set up our autoloaders
			//

			foreach($config->getAllByType('array', '@autoloading') as $autoloading_config) {
				settype($autoloading_config['standards'], 'array');
				settype($autoloading_config['map'], 'array');

				foreach ($autoloading_config['standards'] as $standard => $transform_callback) {
					$this->loadingStandards[strtolower($standard)] = $transform_callback;
				}

				$this->loaders = array_merge($this->loaders, $autoloading_config['map']);
			}

			//
			// Assign our configuration object to a child, and swap it for some data
			//

			$this->children['config'] = $config;
			$config                   = $this['config']->get('array', '@inkwell');

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
			$this->executionMode   = self::DEFAULT_EXECUTION_MODE;

			if (isset($config['execution_mode'])) {
				if (in_array($config['execution_mode'], $valid_execution_modes)) {
					$this->executionMode = $config['execution_mode'];
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
			} elseif ($this->checkExecutionMode('development')) {
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
				$this->writeDirectory = $this->getRoot(NULL, $write_directory);
			} else {
				$this->writeDirectory = $write_directory;
			}

			return $this;
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
		 * Initializes a class by calling its __init() method if available
		 *
		 * @static
		 * @access protected
		 * @param string $class The class to initialize
		 * @return bool Whether or not the initialization was successful
		 */
		public function initializeClass($class)
		{
			//
			// Can't initialize a class that's not loaded
			//

			if (!class_exists($class, FALSE)) {
				return FALSE;
			}

			//
			// Classes cannot be initialized twice
			//

			if (in_array($class, $this->initializedClasses)) {
				return TRUE;
			}

			$init_callback = [$class, self::INITIALIZATION_METHOD];

			//
			// If there's no __init we're done
			//

			if (!is_callable($init_callback)) {
				return TRUE;
			}

			$method     = end($init_callback);
			$reflection = new \ReflectionMethod($class, $method);

			//
			// If __init is not custom, we're done
			//

			if ($reflection->getDeclaringClass()->getName() != $class) {
				return TRUE;
			}

			//
			// Determine class configuration and call __init with it
			//

			$class_config = $this['config']->get('array', $class);
			$element_id   = $this['config']->elementize($class);

			try {
				if (call_user_func($init_callback, $this, $class_config, $element_id)) {
					self::$initializedClasses[] = $class;
					return TRUE;
				}
			} catch (Flourish\Exception $e) {}

			return FALSE;
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
					$regex = str_replace('*', '.*', str_replace('\\', '\\\\', $test));
					$match = preg_match('#^' . $regex . '$#', $class);
				} elseif (class_exists($test)) {
					$test  = [$test, self::MATCH_CLASS_METHOD];
					$match = is_callable($test) ? call_user_func($test, $class) : FALSE;
				} else {
					$match = TRUE;
				}

				if (class_exists($class, FALSE)) {

					//
					// Prevent recursive autoloads from going too far
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

					$base_dir     = trim($target, '/\\' . DS);
					$class_path   = $this->transformClass($standard, $class);
					$include_file = $this->getRoot() . DS . $base_dir . DS . $class_path;

					if (file_exists($include_file)) {

						if ($class == 'Dotink\Inkwell\Response') {
							var_dump($include_file);
							Flourish\Core::expose(Flourish\Core::backtrace());
						}

						include_once $include_file;

						if (class_exists($class, FALSE)) {

							//
							// Map any available configuration to this class
							//

							if (isset($this['config'])) {
								$this['config']->map($class, $base_dir . DS . $class_path);

								if (is_array($interfaces = class_implements($class, FALSE))) {
									return (in_array('Dotink\Interfaces\Inkwell', $interfaces))
										? self::initializeClass($class)
										: TRUE;
								}
							}
						}
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
		 * Registers a factory
		 *
		 * @access public
		 * @param string $alias The alias to register the factory under
		 * @param string $class The class to register
		 * @param string|Closure The factory
		 */
		public function register($alias, $class, $factory)
		{
			$alias = strtolower($alias);

			if (!isset($this->factories[$alias])) {
				$this->factories[$alias] = array();
			}

			$this->factories[$alias][$class] = $factory;
		}


		/**
		 * Runs the application with a provided Request
		 *
		 * @access public
		 * @param Request $request
		 * @return integer The return value
		 */
		public function run($request)
		{
			$global_routes      = $this['config']->get('array', '@routes');
			$controller_configs = $this['config']->getAllByType('array', 'Controller');
			$ordered_routes     = array();
			$ordering_index     = array();

			foreach ($controller_configs as $config) {
				if (!isset($config['routes']) || !is_array($config['routes'])) {
					continue;
				}

				$route_prefix = isset($config['base_url'])
					? rtrim((string) $config['base_url'], '/')
					: NULL;

				foreach ($config['routes'] as $route => $target) {
					$specificity = 0;

					//
					// TODO: Calculate specificity of $route
					//

					$ordering_index[$base_url . $route] = $target;
					$ordered_routes[$base_url . $route] = $specificity;
				}
			}

			asort($ordered_routes);

			$routes = $this->create('routes', [self::ROUTES_INTERFACE], $global_routes);

			foreach (array_keys($ordered_routes) as $route) {
				$routes->link($route, $ordering_index[$route]);
			}

			$response = $routes->run($request);

			return $response;
		}


		/**
		 * Sets a Root Directory
		 *
		 * @access protected
		 * @param string $key The key to set a root directory for
		 * @param string $root_directory The root directory
		 * @return void
		 */
		protected function setRoot($key, $root_directory)
		{
			$root_directory    = str_replace('/', DS, rtrim($root_directory, '/\\' . DS));
			$this->roots[$key] = !preg_match(REGEX_ABSOLUTE_PATH, $root_directory)
				? realpath($this->roots[NULL] . DS . $root_directory)
				: realpath($root_directory);

			if (!is_dir($this->roots[$key])) {
				throw new Flourish\ProgrammerException(
					'Cannot set root directory "%s", directory does not exist',
					$root_directory
				);
			}

			return $this->roots[$key];
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

			if (!isset($this->loadingStandards[$standard])) {
				throw new Flourish\ProgrammerException(
					'Cannot transform class using "%s", standard not registered',
					$standard
				);
			}

			return call_user_func($this->loadingStandards[$standard], $class);
		}
	}
}
