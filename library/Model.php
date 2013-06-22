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
		 * Avaiable application databases
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $databases = array();


		/**
		 * Simple and local namespace to database name cache
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $namespaces = array();


		/**
		 * The configured root directory for models
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $rootDirectory = NULL;


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
			self::$databases     = $app['databases'];
			self::$rootDirectory = $app->getRoot(__CLASS__);

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

					//
					// TODO: Centralize
					//

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
		 * Add relevant build information to a scaffolder
		 *
		 * @static
		 * @access public
		 * @param Scaffolder The scaffolder instance for making
		 * @param array The array of data passed to build
		 * @return mixed A non-FALSE value if the building succeeds
		 */
		static public function __build($scaffolder, $data)
		{
			$class = $scaffolder->getClass();
			$file  = self::$rootDirectory . DS . str_replace('\\', '/', $class) . '.php';

			return $scaffolder->setOutputFile($file);
		}


		/**
		 * Add relevant make information to a scaffolder
		 *
		 * @static
		 * @access public
		 * @param Scaffolder The scaffolder instance for making
		 * @param array The array of data passed to build
		 * @return mixed A non-FALSE value if the making succeeds
		 */
		static public function __make($scaffolder, $data = array())
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
		 * Determine if a class might be a child of this one
		 *
		 * @static
		 * @access public
		 * @return mixed A non-FALSE value if the class matches
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
		 * Loads meta-data for Doctrine 2 for this class.
		 *
		 * Our primary concern here is making sure this is set as a mapped super clss and if not
		 * calling the parent Dub\Model method which actually configures our class.
		 *
		 * @static
		 * @access public
		 * @return void
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
		 * Search for a model class, retrieving database and repository in the process.
		 *
		 * @static
		 * @access protected
		 *
		 */
		static private function search($class, &$database = NULL, &$repository = NULL)
		{
			//
			// TODO: Centralize
			//

			$class_parts = explode('\\', $class);
			$short_name  = array_pop($class_parts);
			$namespace   = implode('\\', $class_parts);

			if (!$database) {
				$database = self::$databases->lookup($namespace);
			}

			if ($database && isset(self::$databases[$database])) {
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
		 * Call an anonymous function with an instance of a named database for a given action
		 *
		 * @static
		 * @access private
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
		 * Call an anonymous function with instances of named databases for a given action
		 *
		 * @static
		 * @access private
		 */
		static private function iterateOn($databases, $action, $callback) {
			settype($databases, 'array');

			foreach ($databases as $database) {
				self::callOn($database, $action, $callback);
			}
		}


		/**
		 * Resolve the best database name for a model class
		 *
		 * If no database is registered for the model's namespace then default will be returned.
		 * This is primarily used to convert null value to defaults.  If any database name
		 * or an array of databases is specified it will return the original value without
		 * question.
		 *
		 * @static
		 * @access private
		 * @return string The name of the best database to try for the model
		 */
		static private function resolveDatabase($database, $model)
		{
			if (!empty($database)) {
				return $database;
			}

			//
			// TODO: Centralize
			//

			$model_class     = get_class($model);
			$model_namespace = !isset(self::$namespaces[$model_class])
				? implode('\\', array_slice(explode('\\', $model_class), 0, -1))
				: self::$namespaces[$model_class];

			return $database = self::$databases->lookup($model_namespace)
				? $database
				: 'default';
		}


		/**
		 * Determine if a record is detached from a given database
		 *
		 * @access public
		 * @param string $database
		 * @return boolean TRUE if the record is detached, FALSE otherwise
		 */
		public function isDetached($database = NULL)
		{
			$database = self::resolveDatabase($database, $this);

			self::callOn($database, 'check if state is detached', function($database) {
				parent::isDetached($database);
			});
		}


		/**
		 * Determine if a record is manged by a particular database
		 *
		 * @access public
		 * @param string $database
		 * @return boolean TRUE if the record is managed, FALSE otherwise
		 */
		public function isManaged($database = NULL)
		{
			$database = self::resolveDatabase($database, $this);

			self::callOn($database, 'check if state is managed', function($database) {
				parent::isManaged($database);
			});
		}


		/**
		 * Determine if a record is new to a particular database
		 *
		 * @access public
		 * @param string $database
		 * @return boolean TRUE if the record is new, FALSE otherwise
		 */
		public function isNew($database = NULL)
		{
			$database = self::resolveDatabase($database, $this);

			self::callOn($database, 'check if state is new', function($database) {
				parent::isNew($database);
			});
		}


		/**
		 * Determine if a record is removed from a particular database
		 *
		 * @access public
		 * @param string $database
		 * @return boolean TRUE if the record is removed, FALSE otherwise
		 */
		public function isRemoved($database = NULL)
		{
			$database = self::resolveDatabase($database, $this);

			self::callOn($database, 'check if state is removed', function($database) {
				parent::isRemoved($database);
			});
		}


		/**
		 * Persists a record in any number of databases
		 *
		 * @access public
		 * @param string $database
		 * @param ...
		 * @return void
		 */
		public function store($database = NULL)
		{
			$databases = self::resolveDatabase(func_get_args(), $this);

			self::iterateOn($databases, __FUNCTION__, function($database) {
				parent::store($database);
			});
		}


		/**
		 * Removes a record from any number of databases.
		 *
		 * @access public
		 * @param string $database
		 * @param ...
		 * @return void
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
