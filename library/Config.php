<?php namespace Dotink\Inkwell
{
	use App;
	use Dotink\Flourish;
	use Dotink\Interfaces;

	/**
	 * Config class responsible for building and representing configuration information as well
	 * as providing accessor methods to query the configuration.
	 *
	 * @copyright Copyright (c) 2012, Matthew J. Sahagian
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 *
	 * @license Please reference the LICENSE.txt file at the root of this distribution
	 *
	 * @package Dotink\Inkwell
	 */
	class Config implements Interfaces\Config
	{
		const DEFAULT_CONFIG          = 'default';
		const CONFIG_BY_TYPES_ELEMENT = '__TYPES__';

		const ELEMENT_SEPARATOR       = '.';


		/**
		 * The cache file for the config
		 *
		 * @access private
		 * @var string
		 */
		private $cacheFile = NULL;


		/**
		 * The directory from which the configuration was built
		 *
		 * @access private
		 * @var string
		 */
		private $configPath = NULL;


		/**
		 * The configuration data
		 *
		 * @access private
		 * @var array
		 */
		private $data = array();


		/**
		 * Element Translations
		 *
		 * @access private
		 * @var array
		 */
		private $elementTranslations = array();


		/**
		 *
		 */
		private $name = NULL;


		/**
		 * Creates a configuration
		 *
		 * @static
		 * @access public
		 * @param string $type The configuration type
		 * @param array $data The configuration data
		 * @return array $data The configuration data
		 */
		static public function create($types, Array $data = NULL)
		{
			if (!is_array($types)) {
				$types = array($types);
			}

			array_map('strtolower', $types);

			return ['types' => $types, 'data' => $data];
		}


		/**
		 * Generates a configuration element ID from a path
		 *
		 * @static
		 * @access private
		 * @param string $path The path to generate an ID for
		 */
		static private function generateElementId($path)
		{
			$path = str_replace(DS, '/', $path);
			$path = new App\Text($path);

			return md5($path->underscorize());
		}

		/**
		 * Normalizes a configuration value to a typehint
		 *
		 * @static
		 * @access private
		 * @return mixed The configuration element or default empty value if none is found
		 */
		static private function normalize($config, $typehint)
		{
			$type     = strtolower(gettype($config));
			$typehint = strtolower($typehint);

			if ($type != $typehint) {
				switch ($typehint) {
					case 'array':
						return array();
					case 'string':
						return '';
					case 'bool':
					case 'boolean':
						return FALSE;
					case 'int':
					case 'float':
					case 'integer':
						return 0;
				}
			}

			return $config;
		}


		/**
		 * Gets the defined/translated class for a particular element ID
		 *
		 * @access public
		 * @param string $element The application element ID to translate
		 * @return string The name of the class which matches the element ID, NULL if not found.
		 */
		public function classize($element)
		{
			if (!isset($this->elementTranslations[$element])) {
				return NULL;
			}

			return $this->elementTranslations[$element];
		}


		/**
		 * Get an application element ID from a class
		 *
		 * @access public
		 * @param string $class The class name
		 * @return string The element ID for the class, NULL if not found.
		 */
		public function elementize($class)
		{
			if (!($element = array_search($class, $this->elementTranslations))) {
				return NULL;
			}

			return $element;
		}


		/**
		 * Gets a configuration element.
		 *
		 * Configuration elements must be typehinted.  In the event that the configuration element
		 * does not match the typehint, an empty value of the given type will be returned.  If
		 * the typehint is not valid, NULL will be returned.
		 *
		 * @access public
		 * @param string $typehint The typehint for the config element
		 * @param string $class The class to get the configuration for
		 * @param string $element The configuration element to get
		 * @return mixed The configuration element or default empty value if none is found
		 */
		public function get($typehint = 'array', $class = NULL, $element = NULL)
		{
			$config = NULL;

			if ($class !== NULL) {
				$element_id = $this->elementize($class);

				if ($element_id && isset($this->data[$element_id])) {
					$config = $this->data[$element_id];

					if ($element) {
						foreach (explode(self::ELEMENT_SEPARATOR, $element) as $sub_element) {
							if (isset($config[$sub_element])) {
								$config = $config[$sub_element];
							} else {
								$config = NULL;
							}
						}
					}
				}

			} else {
				$config = $this->data;
			}

			return self::normalize($config, $typehint);
		}


		/**
		 * Gets config elements for configs matching a given type
		 *
		 * @access public
		 * @param string $typehint The typehint for the configuration elements
		 * @param string $type The config type to get configuration elements for
		 * @param string $element The configuration element to get for each config
		 * @return array The config data for each config matching the type, keys are element ids
		 */
		public function getByType($typehint = 'array', $type = NULL, $element = NULL)
		{
			if ($type !== NULL) {
				$data = array();
				$type = strtolower($type);

				if ($type[0] == '@') {
					$element_id        = $this->elementize($type);
					$data[$element_id] = $this->get('array', $type, $element);

					//
					// The above line will get the core config for the pseudo-class, but we need
					// to modify the $element for all future configs by prepending the $type
					// to the original element if it exists.
					//

					if ($element) {
						$element = $type . self::ELEMENT_SEPARATOR . $element;
					} else {
						$element = $type;
					}
				}

				if (isset($this->data[self::CONFIG_BY_TYPES_ELEMENT][$type])) {
					$configs_by_type = $this->data[self::CONFIG_BY_TYPES_ELEMENT][$type];
					$sub_elements    = explode(self::ELEMENT_SEPARATOR, $element);

					foreach ($configs_by_type as $element_id => $config) {
						if ($sub_elements[0]) {
							foreach ($sub_elements as $sub_element) {
								if (isset($config[$sub_element])) {
									$config = $config[$sub_element];
								} else {
									$config = NULL;
								}
							}
						}

						$data[$element_id] = self::normalize($config, $typehint);
					}
				}

			} else {
				$data = $this->data[self::CONFIG_BY_TYPES_ELEMENT];
			}

			return $data;
		}


		/**
		 * Load a configuration
		 *
		 * @param string $directory The directory to load configs from
		 * @param string $name The name of the config to load
		 * @return Config The config object for method chaining
		 */
		public function load($directory, $name = NULL)
		{
			$directory  = rtrim($directory, '/\\' . DS);
			$directory  = str_replace('/', DS, $directory);

			if (!preg_match(REGEX\ABSOLUTE_PATH, $directory)) {
				throw new Flourish\ProgrammerException(
					'Directory "%s" is invalide, must be absolute path',
					$directory
				);
			}

			$this->name       = $name ?: self::DEFAULT_CONFIG;
			$this->cacheFile  = $directory . DS . '.' . $this->name;
			$this->configPath = $directory . DS . $this->name;
			$this->data       = [self::CONFIG_BY_TYPES_ELEMENT => []];

			if (is_readable($this->cacheFile) && ($data = @unserialize($this->cacheFile))) {
				$this->data = $data;

			} elseif (is_readable($this->configPath)) {
				$this->build();

 			} else {
				throw new Flourish\ProgrammerException(
					'Cannot build configuration, directory "%s" is not readable.',
					$directory
				);
 			}

			return $this;
		}


		/**
		 * Maps a class to an element ID using the config path
		 *
		 * The configuration path must be relative to the configuration directory.  Additional
		 * normalization will be done to ensure that it is in underscore notation and that
		 * directory separators will match.
		 *
		 * @param string $class The class to map
		 * @param string $config_path The relative path for the class conf
		 */
		public function map($class, $config_path)
		{
			$element_id = self::generateElementId($config_path);

			if (isset($this->elementTranslations[$element_id])) {
				throw new Flourish\ProgrammerException(
					'Cannot map class "%s" to element id "%s", already mapped',
					$class,
					$element_id
				);
			}

			$this->elementTranslations[$element_id] = $class;
		}


		/**
		 * Writes the configuration out to a cache file
		 *
		 * @access public
		 */
		public function write($file = NULL)
		{
			if (!$file) {
				$file = $this->cacheFile;
			} else {
				$file = !preg_match(REGEX\ABSOLUTE_PATH, $file)
					? dirname($this->cacheFile) . $file
					: $file;
			}

			if (!file_put_contents($file, serialize($this->data))) {
				throw new Flourish\EnvironmentException(
					'Unable to write configuration file to "%s", check director and permissions',
					$file
				);
			}
		}


		/**
		 * Build the configuration
		 *
		 * This will attempt to use a cached copy of the configuration if it exists.  If it does
		 * not exist or is invalid, it will build from the provided directory.  To clear the
		 * cached version, please see the reset() method.
		 *
		 * @access public
		 * @param string $directory The directory to build from
		 * @param integer $depth The depth of the build process (used internally / recursively)
		 * @return void
		 */
		private function build($directory = NULL, $depth = 0)
		{
			$directory = $directory ?: $this->configPath;
 			$types_ref =& $this->data[self::CONFIG_BY_TYPES_ELEMENT];

			//
			// Loads each PHP file into a configuration element named after the file.
			//

			foreach (glob($directory . DS . '*.php') as $file) {

				$config_path =  implode('/', array_slice(explode(DS, $file), ($depth + 1) * -1));
				$element_id  =  self::generateElementId($config_path);
				$current     =  include($file);

				if (isset($current['data'])) {
					$this->data[$element_id] = $current['data'];

					if (isset($this->data[$element_id]['class'])) {
						$this->map($this->data[$element_id]['class'], $config_path);
					}

				} else {
					$this->data[$element_id] = array();
				}

				if (isset($current['types'])) {

					settype($current['types'], 'array');

					foreach ($current['types'] as $type) {
						$type = strtolower($type);

						if ($type == 'core') {
							$config_name = '@' . str_replace('.php', '', $config_path);
							$this->map($config_name, $config_path);
						}

						if (!isset($types_ref[$type])) {
							$types_ref[$type] = array();
						}

						$types_ref[$type][$element_id] =& $this->data[$element_id];
					}
				}
			}

			//
			// Ensures we recusively scan all directories and merge all configurations.
			//

			foreach (glob($directory . DS . '*', GLOB_ONLYDIR) as $directory) {
				$this->build($directory, $depth + 1);
			}

			//
			// Lastly, if this configuration name is not the default, merge it with it.
			//
			if ($depth == 0 && $this->name !== self::DEFAULT_CONFIG) {
				try {
					$root_directory = dirname($this->configPath);
					$default_config = new Config(self::DEFAULT_CONFIG);
					$this->data     = array_replace_recursive(
						$default_config->build($root_directory)->data,
						$this->data
					);

				} catch (Flourish\Exception $e){

					//
					// If we cannot merge the default data, we'll have to assume the non-default
					// is complete.
					//

				}
			}
		}
	}
}
