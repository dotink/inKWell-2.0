<?php namespace Dotink\Inkwell
{
	return Config::create(['Core'], [
		'actions' => [
			'/system_information' => 'phpinfo',
			'/test/[!:action]' => 'TestController::[action]'
		],
	]);
}
