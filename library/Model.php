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
		static private $dynamicLoading = FALSE;


		/**
		 *
		 */
		static public function __init($app, Array $config = array())
		{
			foreach ($app['config']->getByType('array', 'model') as $eid => $model_config) {
				if (isset($model_config['class']) && isset($model_config['auto_map'])) {
					if (!$model_config['auto_map']) {
						continue;
					}

					if (strpos($model_config['auto_map'], '::') !== FALSE) {
						$auto_map_parts = explode('::', $model_config['auto_map']);
						$database_name  = $auto_map_parts[0];
						$repository     = $auto_map_parts[1];

					} else {
						$database_name  = 'default';
						$repository     = $model_config['auto_map'];
					}

					if (!isset($app['databases'][$database_name])) {
						continue;
					}

					$mapping = $app['databases']->reflectSchema($database_name, $repository);

					parent::configure($model_config['class'], $mapping);

					if (!self::$dynamicLoading) {
						spl_autoload_register('Dotink\Dub\Model::dynamicLoader');

						self::$dynamicLoading = TRUE;
					}
				}
			}
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
	}
}
