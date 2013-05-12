<?php namespace Dotink\Inkwell
{
	use App;
	use Dotink\Dub;
	use Dotink\Flourish;
	use Dotink\Interfaces;

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
		 *
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
						Dub\ModelConfiguration::store(
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
					$short_name  = end($class_parts);
					$namespace   = implode('\\', $class_parts);

					$repository = Dub\ModelConfiguration::makeRepositoryName($short_name);

				} else {
					$repository = $model_config['schema']['repo'];
				}

				Dub\ModelConfiguration::reflect(
					$model_config['class'],
					self::$databases[$database_name],
					$repository
				);
			}
		}


		/**
		 *
		 */
		static public function __match($class)
		{
			try {
				Dub\ModelConfiguration::load($class);

				return TRUE;
			} catch (Flourish\EnvironmentException $e) {}

			$class_parts = explode('\\', $class);
			$short_name  = end($class_parts);
			$namespace   = implode('\\', $class_parts);

			if (!($database = self::$databases->lookup($namespace))) {
				$database = 'default';
			}

			if (isset(self::$databases[$database])) {
				$connection = self::$databases[$database]->getConnection();
				$schema     = $connection->getSchemaManager();
				$repository = Dub\ModelConfiguration::makeRepositoryName($short_name);

				$available  = array_map(function($table) {
					return $table->getName();
				}, $schema->listTables());

				return in_array($repository, $available);
			}

			return FALSE;
		}


		/**
		 *
		 */
		static public function configureMetadata($builder) {
			$class = get_called_class();

			if ($class == __CLASS__) {
				$builder->setMappedSuperclass();
			}
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
