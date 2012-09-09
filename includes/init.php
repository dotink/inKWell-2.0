<?php namespace Dotink\Inkwell
{
	include 'functions.php';
	include 'core.php';
	include 'constants.php';

	//
	// The IW::init() method takes two arguments, although the second is optional it is shown
	// here for clarity.  The first argument is the application root by default the parent of
	// the parent of wherever this file is found.  The second argument is our base library
	// directory.  Although the structure inside the library must remain consistent, it is
	// possible to move or rename the base folder.
	//

	$app = IW::init(realpath(dirname(__DIR__)), 'library');

	//
	// Register our dependency containers
	//

	$app->register('config', 'Dotink\Inkwell\Config',
		function($dir, $name = NULL) {
			return new Config($dir, $name);
		}
	);

	$app->register('request', 'Dotink\Inkwell\Request',
		function($method = NULL, $accept = NULL, $url = NULL, $data = NULL) {
			return new Request($method, $accept, $url, $data);
		}
	);

	$app->register('response', 'Dotink\Inkwell\Response');
	$app->register('routes',   'Dotink\Inkwell\Routes');

	//
	// Include configuration logic and return the app
	//

	include 'config.php';

	return $app;
}
