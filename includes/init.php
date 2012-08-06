<?php namespace Dotink\Inkwell {

	include 'core.php';
	include 'functions.php';

	$app = IW::init(realpath(dirname(__DIR__)));

	$app->register('config', 'Dotink\Inkwell\Config', function($dir, $name = NULL) {
		return new Config($dir, $name);
	});

	//
	// Feel free to change how your configuration is done below.  You can point it to a different
	// base directory or change the name based on the server host, i.e. dev.example.com might
	// use a config named 'development' while www.example.com will use 'production'
	//

	$config_name = isset($_SERVER['IW_CONFIG']) ? $_SERVER['IW_CONFIG'] : NULL;
	$config_dir  = NULL;

	return $app->config($config_name, $config_dir);
}