<?php namespace Dotink\Inkwell
{
	use App;
	use Dotink\Dub;
	use Dotink\Flourish;
	use Dotink\Interfaces;
	use Dotink\Dub\ModelConfiguration;
	use Doctrine\ORM\Mapping\ClassMetadata;
	use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;


	/**
	 * Model class responsible for ownage
	 *
	 * @copyright Copyright (c) 2012, Matthew J. Sahagian
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 *
	 * @license Please reference the LICENSE.txt file at the root of this distribution
	 *
	 * @package Dotink\Inkwell
	 */
	class Model extends Dub\Model implements Interfaces\Inkwell
	{
		/**
		 *
		 */
		static private $databases = array();


		/**
		 *
		 */
		static private $namespaces = array();


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
			self::$databases = $app['databases'];

			foreach ($app['config']->getByType('array', 'Model') as $eid => $model_config) {
				if (!isset($model_config['class'])) {
					continue;
				}

				if (empty($model_config['reflect'])) {
					if (isset($model_config['schema'])) {
						ModelConfiguration::store(
							$model_config['class'],
							$model_config['schema']
						);
					}

					continue;
				}

				$database_name = $model_config['reflect'];

				if (!isset(self::$databases[$database_name])) {
					throw new Flourish\ProgrammerException(
						'Cannot reflect model %s to database %s, database not configured',
						$model_config['class'],
						$database_name
					);
				}

				if (empty($model_config['schema']['repo'])) {
					$class_parts = explode('\\', $model_config['class']);
					$short_name  = array_pop($class_parts);
					$namespace   = implode('\\', $class_parts);

					$repository = ModelConfiguration::makeRepositoryName($short_name);

				} else {
					$repository = $model_config['schema']['repo'];
				}

				ModelConfiguration::reflect(
					$model_config['class'],
					self::$databases[$database_name],
					$repository
				);
			}

			return TRUE;
		}


		/**
		 *
		 */
		static public function __make($scaffolder)
		{
			$class = $scaffolder->getClass();

			try {
				$config = ModelConfiguration::load($class);
			} catch (Flourish\EnvironmentException $e) {
				if (!self::search($class, $database, $repository)) {
					return FALSE;
				}

				$database = self::$databases[$database];
				$config   = ModelConfiguration::reflect($class, $database, $repository);
			}

			return $scaffolder
				-> setTemplate('classes/Dotink/Inkwell/Model')
				-> make([
					'fields' => $config->getFields()
				]);
		}


		/**
		 *
		 */
		static public function __match($class)
		{
			try {
				ModelConfiguration::load($class);
				return TRUE;
			} catch (Flourish\EnvironmentException $e) {}

			if (!self::$databases) {
				return FALSE;
			}

			return self::search($class);
		}


		/**
		 *
		 */
		static public function loadMetadata(ClassMetadata $metadata)
		{
			$class   = get_called_class();
			$builder = new ClassMetadataBuilder($metadata);

			if ($class == __CLASS__) {
				$builder->setMappedSuperclass();
			} else {
				parent::loadMetaData($metadata);
			}
		}


		/**
		 *
		 */
		static public function search($class, &$database = NULL, &$repository = NULL)
		{
			$class_parts = explode('\\', $class);
			$short_name  = array_pop($class_parts);
			$namespace   = implode('\\', $class_parts);

			if (!$database) {
				$database = self::$databases->lookup($namespace);
			}

			if (isset(self::$databases[$database])) {
				$connection = self::$databases[$database]->getConnection();
				$schema     = $connection->getSchemaManager();

				if (!$repository) {
					$repository = ModelConfiguration::makeRepositoryName($short_name);
				}

				$available = array_map(function($table) {
					return $table->getName();
				}, $schema->listTables());

				return in_array($repository, $available);
			}

			return FALSE;
		}


		/**
		 *
		 */
		static private function callOn($database, $action, $callback) {
			if (!isset(self::$databases[$database])) {
				throw new Flourish\ProgrammerException(
					'Cannot %s on model of type %s on %s database, no such database',
					$action, get_class($this), $database
				);
			}

			$callback(self::$databases[$database]);
		}


		/**
		 *
		 */
		static private function iterateOn($databases, $action, $callback) {
			settype($databases, 'array');

			foreach ($databases as $database) {
				if (!isset(self::$databases[$database])) {
					throw new Flourish\ProgrammerException(
						'Cannot %s model of type %s on %s database, no such database',
						$action, get_class($this), $database
					);
				}

				$callback(self::$databases[$database]);
			}
		}


		/**
		 *
		 */
		static private function resolveDatabase($database, $model)
		{
			if (!empty($database)) {
				return $database;
			}

			$model_class     = get_class($model);
			$model_namespace = !isset(self::$namespaces[$model_class])
				? implode('\\', array_slice(explode('\\', $model_class), 0, -1))
				: self::$namespaces[$model_class];

			return $database = self::$databases->lookup($model_namespace)
				? $database
				: 'default';
		}


		/**
		 *
		 */
		public function isDetached($database = NULL)
		{
			$database = self::resolveDatabase($database, $this);

			self::callOn($database, 'check if state is detached', function($database) {
				parent::isDetached($database);
			});
		}


		/**
		 *
		 */
		public function isManaged($database = NULL)
		{
			$database = self::resolveDatabase($database, $this);

			self::callOn($database, 'check if state is managed', function($database) {
				parent::isManaged($database);
			});
		}


		/**
		 *
		 */
		public function isNew($database = NULL)
		{
			$database = self::resolveDatabase($database, $this);

			self::callOn($database, 'check if state is new', function($database) {
				parent::isNew($database);
			});
		}


		/**
		 *
		 */
		public function isRemoved($database = NULL)
		{
			$database = self::resolveDatabase($database, $this);

			self::callOn($database, 'check if state is removed', function($database) {
				parent::isRemoved($database);
			});
		}


		/**
		 *
		 */
		public function store($database = NULL)
		{
			$databases = self::resolveDatabase(func_get_args(), $this);

			self::iterateOn($databases, __FUNCTION__, function($database) {
				parent::store($database);
			});
		}


		/**
		 *
		 */
		public function remove($database = NULL)
		{
			$databases = self::resolveDatabase(func_get_args(), $this);

			self::iterateOn($databases, __FUNCTION__, function($database) {
				parent::remove($database);
			});
		}

	}
}
