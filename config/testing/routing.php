<?php namespace Dotink\Inkwell
{
	return Config::create(['Core'], [
		'actions' => [
			'/system_information' => 'phpinfo'
		],
	]);
}
