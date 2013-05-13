<?php namespace Dotink\Inkwell
{
	return Config::create(['Core'], [

		'disabled' => FALSE,

		'map' => [

			//
			//
			//

			'default' => [

				//
				// The namespace of models associated with this database as well as the namespace
				// where proxies will be stored.
				//

				'namespace' => 'App\Model',
				'connection' => [

					//
					// Valid Drivers: pdo_sqlite, pdo_mysql, pdo_pgsql, pdo_oci, pdo_sqlsrv
					//

					'driver' => NULL,
					'dbname' => NULL,
					'host'   => NULL,
					'port'   => NULL,

					//
					// The path (string) or memory (boolean) settings are used in place of dbname,
					// host, and port for pdo_sqlite.
					//
					// 'memory' => TRUE,
					// 'path'   => NULL,
					//
					// For pdo_mysql you can use a unix socket path instead of host and port
					//
					// 'unix_socket' => NULL,
					//

					'user'     => NULL,
					'password' => NULL,
					'charset'  => 'utf-8'
				]
			],
		],
	]);
}