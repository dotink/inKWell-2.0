<?php namespace Dotink\Inkwell
{
	return Config::create(['Core'], [

		'types' => [
			//
			// 'ts_vector' => 'Dotink\Dub\Type\TSVector'
			//
		],

		'map' => [
			'default' => [
				'connection' => [
					'driver' => 'pdo_sqlite',
					'dbname' => NULL,
					'path'   => implode(
						DIRECTORY_SEPARATOR,
						[__DIR__, '..', '..', 'external', 'testing', 'sample.db']
					)
				],

				'types' => [
					//
					// 'tsvector' => 'ts_vector'
					//
				]
			],
		],
	]);
}
