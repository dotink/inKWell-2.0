<?php namespace Dotink\Inkwell
{
	return Config::create(['Core'], [
		'actions' => [
			'/system_information' => 'phpinfo',
			'/test/'              => 'TestController::main',
			'/test/[!:action]'    => 'TestController::[action]',
			'/'                   => 'HomeController::main'
		]
	]);
}
