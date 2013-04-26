<?php namespace Dotink\Inkwell
{
	use App;
	use Dotink\Flourish;
	use Dotink\Interfaces;
	use ArrayAccess;

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
	class IW implements ArrayAccess
	{
		const MAGIC_NAMESPACE          = 'App';

		const INITIALIZATION_METHOD    = '__init';
		const MATCH_CLASS_METHOD       = '__match';

		const DEFAULT_CONFIG_DIRECTORY = 'config';
		const DEFAULT_WRITE_DIRECTORY  = 'assets';
		const DEFAULT_CACHE_DIRECTORY  = 'cache';
		const DEFAULT_EXECUTION_MODE   = 'development';

		const CONFIG_INTERFACE         = 'Dotink\Interfaces\Config';
		const RESPONSE_INTERFACE       = 'Dotink\Interfaces\Response';
		const ROUTER_INTERFACE         = 'Dotink\Interfaces\Router';


		/**
		 * A list of just-in-time aliases for the autoloader
		 *
		 * @access private
		 * @var array
		 */
		private $aliases = array();


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
			$path  = App\Text::create($path)->underscorize();

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
		 * @access public
		 * @param string $root_directory The root directory for the application
		 * @param string $library_directory The inKWell core library directory
		 * @return void
		 */
		public function __construct($root_directory, $library_directory = 'library')
		{
			//
			// Set our application root
			//

			$this->addRoot(NULL, $root_directory);

			//
			// Our initial loader map is established.  This will use compatibility transformations,
			// meaning that namespaces will be ignored when loading the classes.
			//

			$this->loaders['Dotink\Inkwell\*']    = $library_directory;
			$this->loaders['Dotink\Flourish\*']   = $library_directory . DS . 'flourish';
			$this->loaders['Dotink\Interfaces\*'] = $library_directory . DS . 'interfaces';
			$this->loaders['Dotink\Traits\*']     = $library_directory . DS . 'traits';

			$composer_autoloader = implode(DS, [
				$this->getRoot(NULL, 'vendor'),
				'autoload.php'
			]);

			if (file_exists($composer_autoloader)) {
				include $composer_autoloader;
			}

			spl_autoload_register([$this, 'loadClass'], true, true);
		}


		/**
		 * Adds an autoloading standard.  This will overload any loading standard with the same
		 * key.
		 *
		 * @access public
		 * @param string $standard The standard to register as
		 * @param Callable $transform_callback The callback to register for transformation
		 * @return void
		 */
		public function addLoadingStandard($standard, $transform_callback)
		{
			$standard = strtolower($standard);

			if (!is_callable($transform_callback)) {
				throw new Flourish\ProgrammerException(
					'Cannot set loading standard "%s", callback is not valid',
					$standard
				);
			}

			return $this->loadingStandards[$standard] = $transform_callback;
		}


		/**
		 *
		 */
		public function addLoadingMap($match, $target)
		{
			if (isset($this->loaders[$match])) {
				throw new Flourish\ProgrammerException(
					'Cannot add loading map for conflicting match key "%s"',
					$match
				);
			}

			$this->loaders[$match] = $target;
		}


		/**
		 * Adds a Root Directory.  This will overload any root directory with the same key.
		 *
		 * @access public
		 * @param string $key The key to set a root directory for
		 * @param string $root_directory The root directory
		 * @return void
		 */
		public function addRoot($key, $root_directory)
		{
			$key            =  strtolower($key);
			$root_directory =  str_replace('/', DS, rtrim($root_directory, '/\\' . DS));
			$root_directory = !preg_match(REGEX\ABSOLUTE_PATH, $root_directory)
				? realpath($this->getRoot() . DS . $root_directory)
				: realpath($root_directory);

			if (!is_dir($root_directory)) {
				throw new Flourish\ProgrammerException(
					'Cannot set root directory "%s", directory does not exist',
					$root_directory
				);
			}

			return $this->roots[$key] = $root_directory;
		}


		/**
		 * Alias a class in the magic namespace to another fully qualified class
		 *
		 * Unlike using class_alias() in the system, this method will place the actual aliasing
		 * within the autoloading logic which allows for aliases themselves to be lazy.
		 *
		 * @access public
		 * @param string|array $alias The class alias to use or an array of aliases to classes
		 * @param string $class The class to alias to
		 * @return void
		 */
		public function alias($alias, $class = NULL)
		{
			if (func_num_args() == 1 && is_array($alias)) {
				foreach ($alias as $alias => $class) {
					$this->aliases[self::MAGIC_NAMESPACE . '\\' . $alias] = $class;
				}
			} else {
				$this->aliases[self::MAGIC_NAMESPACE .  '\\' . $alias] = $class;
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
		 * Create an an instance from the available factories
		 *
		 * @access public
		 * @param string $alias The alias the factory is registered under
		 * @param string|array $interfaces The interfaces it must implement
		 * @param mixed First parameter to factory...
		 * @param ...
		 * @return mixed An object instance of the alias type, implementing the provided interfaces
		 */
		public function create($alias, $interfaces = [], $param = NULL)
		{
			$alias = strtolower($alias);

			if (!isset($this->factories[$alias])) {
				throw new Flourish\ProgrammerException(
					'No classes exist for the alias "%s"',
					$alias
				);
			}

			settype($interfaces, 'array');

			foreach ($this->factories[$alias] as $class => $factory) {

				if (!class_exists($class)) {
					continue;
				}

				if (count(array_diff($interfaces, class_implements($class)))) {
					continue;
				}

				//
				// Only attempt to build if the class exists and it implements the requested
				// interfaces.
				//

				if ($factory === NULL) {
						$result = new $class();

				} elseif (is_callable($factory)) {
						$result = call_user_func_array($factory, array_slice(func_get_args(), 2));

				} elseif (is_callable($factory = $class . '::' . $factory)) {
						$result = call_user_func_array($factory, array_slice(func_get_args(), 2));

				} else {

					//
					// Skip if the factory is not callable
					//

					continue;
				}

				if (!($result instanceof $class)) {
					throw new Flourish\ProgrammerException(
						'Fetched instance is not an instance of class "%s", bad factory',
						$class
					);
				}

				return $result;
			}

			throw new Flourish\ProgrammerException(
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
			$config_root = $config_root ?: $this->getRoot() . DS . self::DEFAULT_CONFIG_DIRECTORY;
			$config      = $this->create('config', [self::CONFIG_INTERFACE]);

			$config->load($config_root, $config_name);

			$this->addRoot('config', $config_root);

			//
			// Set up our libraries.
			//

			foreach ($config->getByType('array', 'Library') as $element_id => $library_config) {
				$class    = $config->classize($element_id);
				$autoload = !empty($library_config['auto_load']);

				if (!$class) {
					throw new Flourish\ProgrammerException(
						'Library %s must define a `class` configuration element',
						$element_id
					);
				}

				if (isset($library_config['root_directory'])) {
					$this->addRoot($element_id, $library_config['root_directory']);

					if ($autoload) {
						$this->addLoadingMap($class, 'IW: ' . $library_config['root_directory']);
					}

				} elseif ($autoload) {
					throw new Flourish\ProgrammerException(
						'Autoloading for class %s enabled, but no `root_directory` defined',
						$class
					);
				}
			}

			//
			// Set up our autoloaders
			//

			foreach ($config->getByType('array', '@autoloading') as $autoloading_config) {

				settype($autoloading_config['standards'], 'array');
				settype($autoloading_config['map'], 'array');

				foreach ($autoloading_config['standards'] as $standard => $transform_callback) {
					$this->addLoadingStandard($standard, $transform_callback);
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

			if (!preg_match(REGEX\ABSOLUTE_PATH, $write_directory)) {
				$this->writeDirectory = $this->getRoot() . DS . $write_directory;
			} else {
				$this->writeDirectory = $write_directory;
			}

			return $this;
		}


		/**
		 * Gets a configured root directory from the configured root directories
		 *
		 * @access public
		 * @param string $key The key or class name to lookup
		 * @param string $default A default root, relative to the application root
		 * @return string A reference to the root directory for "live roots"
		 */
		public function getRoot($key = NULL, $default = NULL)
		{
			if ($key) {
				$key = class_exists($key, FALSE)
					? $this['config']->elementize($key)
					: strtolower($key);
			}

			if (!isset($this->roots[$key]) || $key === NULL) {
				if (!$default) {
					$directory = $this->roots[NULL];
				} else {
					$default   = str_replace('/', DS, rtrim($default, '/\\' . DS));
					$directory = !preg_match(REGEX\ABSOLUTE_PATH, $default)
						? $this->roots[NULL] . DS . $default
						: $default;
				}

			} else {
				$directory = $this->roots[$key];
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
				$write_directory = !preg_match(REGEX\ABSOLUTE_PATH, $sub_directory)
					? $this->getWriteDirectory() . DS . $sub_directory
					: $sub_directory;
			} else {
				$write_directory = $this->writeDirectory;
			}

			if (!is_dir($write_directory)) {
				(new Flourish\Directory($write_directory))->create(0777);
			}

			return rtrim($write_directory, '/\\' . DS);
		}


		/**
		 * Initializes a class by calling its __init() method if available
		 *
		 * @access public
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

			try {
				if (call_user_func($init_callback, $this, $class_config)) {
					$this->initializedClasses[] = $class;
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
			if (isset($this->aliases[$class])) {
				if ($this->loadClass($this->aliases[$class], $loaders)) {
					class_alias($this->aliases[$class], $class);
				}
			}

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
					$class_path   = $this->transformClassToPath($class, $standard);
					$include_file = $this->getRoot() . DS . $base_dir . DS . $class_path;

					if (file_exists($include_file)) {

						include_once $include_file;

						if (class_exists($class, FALSE)) {

							//
							// Map any available configuration to this class
							//

							if (isset($this['config'])) {
								if (!$this['config']->elementize($class)) {
									$this['config']->map($class, $base_dir . DS . $class_path);
								}

								if (is_array($interfaces = class_implements($class, FALSE))) {
									return (in_array('Dotink\Interfaces\Inkwell', $interfaces))
										? $this->initializeClass($class)
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
		public function offsetGet($offset)
		{
			if (!$this->offsetExists($offset)) {
				throw new Flourish\ProgrammerException(
					'Element "%s" not set on parent %s',
					$offset,
					__CLASS__
				);
			}

			return $this->children[$offset];
		}


		/**
		 * Registers a factory
		 *
		 * @access public
		 * @param string $alias The alias to register the factory under
		 * @param string $class The class to register
		 * @param NULL|string|Closure The factory
		 */
		public function register($alias, $class, $factory = NULL)
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
		public function run(Interfaces\Request $request)
		{
			$router   = $this->create('router',   [self::ROUTER_INTERFACE]);
			$response = $this->create('response', [self::RESPONSE_INTERFACE]);

			foreach ($this['config']->getByType('array', '@redirects') as $config) {
				foreach ($config as $type => $redirects) {
					foreach ($redirects as $route => $translation) {
						$router->redirect($route, $translation, $type);
					}
				}
			}

			foreach ($this['config']->getByType('array', '@routing') as $config) {
				$base_url = isset($config['base_url']) && $config['base_url']
					? $config['base_url']
					: NULL;

				if (isset($config['actions']) && is_array($config['actions'])) {
					foreach ($config['actions'] as $route => $action) {
						$router->link($base_url, $route, $action);
					}
				}

				if (isset($config['handlers']) && is_array($config['handlers'])) {
					foreach ($config['handlers'] as $error => $action) {
						$router->handle($base_url, $error, $action);
					}
				}
			}

			return $router->run($request, $response);
		}


		/**
		 * Transforms a class name to a given (registered) standard
		 *
		 * @access private
		 * @param string $class The class to transform
		 * @param string $standard The standard to use (case insensitive), NULL is default/compat
		 * @return string The transformed class to file path according to the standard
		 */
		private function transformClassToPath($class, $standard = NULL)
		{
			if ($standard == NULL) {

				//
				// This is our compatibility standard.  It ignores namespaces altogether
				//

				$class = ltrim($class, '\\');
				$parts = explode('\\', $class);
				$path  = array_pop($parts) . '.php';

			} else {

				//
				// If a standard is defined, we want to use the registered callback for
				// transformation.
				//

				$standard = strtolower($standard);

				if (!isset($this->loadingStandards[$standard])) {
					throw new Flourish\ProgrammerException(
						'Cannot transform class using "%s", standard not registered',
						$standard
					);
				}

				$path = call_user_func($this->loadingStandards[$standard], $class);
			}

			return $path;
		}
	}
}
