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
		const DEFAULT_ROOT   = 'config';
		const DEFAULT_CONFIG = 'default';

		const CONFIG_BY_TYPES_ELEMENT = '__TYPES__';

		/**
		 * The cache file for the config
		 *
		 */
		private $cacheFile = NULL;


		/**
		 * The configuration data
		 *
		 * @access private
		 * @var array
		 */
		private $data = array();


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
		 *
		 */
		static private function build($directory = NULL, $depth = 0)
		{
 			if (!is_readable($directory)) {
				throw new Exception(sprintf(
					'Cannot build configuration, directory "%s" is not readable.',
					$directory
				));
 			}

 			$config          =  array(self::CONFIG_BY_TYPES_ELEMENT => array());
 			$configs_by_type =& $config[self::CONFIG_BY_TYPES_ELEMENT];

			//
			// Loads each PHP file into a configuration element named after the file.
			//

			foreach (glob($directory . DS . '*.php') as $config_file) {
				$element          = array_slice(explode(DS, $config_file), ($depth + 1) * -1);
				$element          = str_replace('.php', '', implode('/', $element));
				$element          = strtolower($element);
				$current_config   = include($config_file);
				$config[$element] = $current_config['data'];

				foreach ($current_config['types'] as $type) {
					if (!isset($configs_by_type[$type])) {
						$configs_by_type[$type] = array();
					}

					$configs_by_type[$type][$element] =& $config[$element];
				}
			}

			//
			// Ensures we recusively scan all directories and merge all configurations.
			//

			foreach (glob($directory . DS . '*', GLOB_ONLYDIR) as $directory) {
				$depth  = $depth + 1;
				$config = array_merge_recursive($config, self::build($directory, $depth));
			}

			return $config;
		}

		/**
		 * Construct a configuration object
		 *
		 */
		public function __construct($app, $directory = NULL)
		{
			$default_directory = implode(DS, [self::DEFAULT_ROOT, self::DEFAULT_CONFIG]);
			$default_directory = implode(DS, [$app->getRoot(), $default_directory]);
			$default_directory = str_replace('/', DS, $default_directory);

			if (!$directory) {
				$directory = $default_directory;
			} elseif (!preg_match(REGEX_ABSOLUTE_PATH, $directory)) {
				$directory = implode(DS, [$app->getRoot(), $directory]);
			}

			$directory       = str_replace('/', DS, $directory);
			$config_name     = pathinfo($directory, PATHINFO_BASENAME);
			$this->cacheFile = dirname($directory) . DS . '.' . $config_name;

			if (is_readable($this->cacheFile) && ($data = @unserialize($this->cacheFile))) {
				$this->data = $data;
			} else {
				$this->data = self::build($directory);

				if ($directory != $default_directory) {
					$default    = new self($app);
					$this->data = array_replace_recursive($this->data, $default->data);
				}
			}
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
				$element = strtolower($element);

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

			if (($type = gettype($config)) !== $typehint) {
				switch ($type) {
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