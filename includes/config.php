<?php namespace Dotink\Inkwell
{
	//
	// Feel free to change how your configuration is done below.  You can point it to a different
	// base directory or change the name based on the server host, i.e. dev.example.com might
	// use a config named 'development' while www.example.com will use 'production'
	//

	$config_dir  = NULL;
	$config_name = isset($_SERVER['IW_CONFIG'])
		? $_SERVER['IW_CONFIG']
		: (
			isset($_ENV['IW_CONFIG'])
				? $_ENV['IW_CONFIG']
				: NULL
		);

	$app->config($config_name, $config_dir);
}
