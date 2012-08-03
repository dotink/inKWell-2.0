<?php

	namespace Dotink\Inkwell;

	include 'core.php';
	include 'functions.php';

	return IW::init(realpath(dirname(__DIR__)), function($app){

		//
		// You can modify the logic below to determine the config directory in different ways.  By
		// default we will try to get this value from the server's IW_CONFIG environment variable,
		// but you might want to change it based on the SERVER_NAME, or something else.
		//

		$config_name  = isset($_SERVER['IW_CONFIG']) ? $_SERVER['IW_CONFIG'] : NULL;
		$config       = new Config($config_name);

		return $config->build($app->getRoot('config'));
	});