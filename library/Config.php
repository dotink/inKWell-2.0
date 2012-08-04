<?php

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

	namespace Dotink\Inkwell;

	use Dotink\Flourish;

	class Config
	{
		const DEFAULT_CONFIG = 'default';

		const CONFIG_BY_TYPES_ELEMENT = '__TYPES__';

		/**
		 * The cache file for the config
		 *
		 *
		 */
		private $cacheFile = NULL;


		/**
		 *
		 *
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
		 * Construct a configuration object
		 *
		 * This does not inherently build the configuration.
		 */
		public function __construct($name = NULL)
		{
			$this->name = $name ?: self::DEFAULT_CONFIG;
		}


		/**
		 * Build the configuration
		 *
		 * This will attempt to use a cached copy of the configuration if it exists.  If it does
		 * not exist or is invalid, it will build from the provided directory.  To clear the
		 * cached version, please see the reset() method.
		 *
		 * @access public
		 *
		 */
		public function build($directory = NULL, $depth = 0)
		{
			if ($depth == 0) {

				$directory = rtrim($directory, '/\\' . DS);
				$directory = str_replace('/', DS, $directory);

				if (!preg_match(REGEX_ABSOLUTE_PATH, $directory)) {
					throw new Flourish\ProgrammerException(
						'Cannot build config "%s" from "%s", must be an absolute path',
						$this->name,
						$directory
					);
				}

				$this->cacheFile  = $directory . DS . '.' . $this->name;
				$this->configPath = $directory . DS . $this->name;

				if (is_readable($this->cacheFile) && ($data = @unserialize($this->cacheFile))) {
					$this->data = $data;

					//
					// We want to return almost immediately if we got good data from our cache
					//

					return $this;
				}

				$this->data = [self::CONFIG_BY_TYPES_ELEMENT => []];
				$directory  = $this->configPath;
			}

 			if (!is_readable($directory)) {
				throw new Flourish\ProgrammerException(
					'Cannot build configuration, directory "%s" is not readable.',
					$directory
				);
 			}

 			$types_ref =& $this->data[self::CONFIG_BY_TYPES_ELEMENT];

			//
			// Loads each PHP file into a configuration element named after the file.
			//

			foreach (glob($directory . DS . '*.php') as $config_file) {

				//
				// We need to generate element IDs that match inKWell's.  So we follow
				// the rules for generating an ID.
				//
				// 1. We only want the tail end of our directory structure based on depth
				// 2. We recombine using the standard separator, and replace word separator
				// 3. MD5 hash the lowerecased result
				//

				$element = array_slice(explode(DS, $config_file), ($depth + 1) * -1);
				$element = str_replace('_', '', implode('/', $element));
				$element = md5(strtolower($element));

				$current = include($config_file);

				$this->data[$element] = isset($current['data']) ? $current['data'] : array();

				if (isset($current['types'])) {
					foreach ($current['types'] as $type) {
						if (!isset($types_ref[$type])) {
							$types_ref[$type] = array();
						}

						$types_ref[$type][$element] =& $this->data[$element];
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

			return $this;
		}


		/**
		 * Get a config element name from a class, taking translations into account
		 *
		 * @static
		 * @access public
		 * @param string $class The class name
		 * @return string The element name for the class
		 */
		static public function elementize($class)
		{
			if (!($element = array_search($class, $this->classTranslations))) {
				$element = Flourish\Grammar::underscorize($class);
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
		 * @param string $element The element to get
		 * @return mixed The configuration element or default empty value
		 */
		public function get($typehint, $element = NULL)
		{
			$config = NULL;

			if ($element !== NULL) {
				if (isset($this->data[$element])) {
					$config = $this->data[$element];

					foreach (array_slice(func_get_args(), 2) as $sub_element) {
						if (isset($config[$sub_element])) {
							$config = $config[$sub_element];
						} else {
							$config = NULL;
						}
					}
				}
			} else {
				$config = $this->data;
			}

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
		 * Writes the configuration out to a cache file
		 *
		 * @access public
		 */
		public function write($file = NULL)
		{
			if (!$file) {
				$file = $this->cacheFile;
			} else {
				$file = !preg_match(REGEX_ABSOLUTE_PATH, $file)
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
	}