<?php namespace Dotink\Inkwell
{
	return Config::create(['Core'], [

		'disabled' => FALSE,

		'connections' => [

			//
			//
			//

			'default' => [

				//
				// Valid Drivers:
				// - pdo_sqlite
				// - pdo_mysql
				// - pdo_pgsql
				// - pdo_oci / oci8
				// - pdo_sqlsrv
				//

				'driver' => NULL,
				'dbname' => NULL,

				//
				// The path (string) or memory (boolean) settings are used in place of dbname,
				// host, and port for pdo_sqlite.
				//
				// 'memory' => TRUE,
				// 'path'   => NULL,
				//

				'host' => NULL,
				'port' => NULL,

				//
				// You can use unix_socket for pdo_mysql instead of host/port
				//
				// 'unix_socket' => NULL,
				//

				'user'     => NULL,
				'password' => NULL,

				//
				// The charset parameter is support for pdo_mysql and pdo_oci / oci8
				//
				// 'charset' => 'utf-8'
				//

			],
		],
	]);
}
