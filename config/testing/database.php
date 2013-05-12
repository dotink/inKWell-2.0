<?php namespace Dotink\Inkwell
{
	return Config::create(['Core'], [
		'map' => [
			'default' => [
				'namespace' => NULL,
				'connection' => [
					'driver' => 'pdo_sqlite',
					'dbname' => NULL,
					'path'   => implode(
						DIRECTORY_SEPARATOR,
						[__DIR__, '..', '..', 'external', 'testing', 'sample.db']
					)
				]
			],
		],
	]);
}
