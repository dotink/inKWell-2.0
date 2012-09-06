<?php namespace Dotink\Inkwell
{
	include 'core.php';
	include 'constants.php';
	include 'functions.php';


	//
	// The IW::init() method takes two arguments, although the second is optional it is shown
	// here for clarity.  The first argument is the application root by default the parent of
	// the parent of wherever this file is found.  The second argument is our base library
	// directory.  Although the structure inside the library directory is built into IW, it is
	// possible to move or rename the base folder.
	//

	$app = IW::init(realpath(dirname(__DIR__)), 'library');


	//
	// Register our dependency containers
	//

	$app->register('config', 'Dotink\Inkwell\Config', function($dir, $name = NULL) {
		return new Config($dir, $name);
	});

	$app->register('request', 'Dotink\Inkwell\Request', function() {
		return new Request();
	});

	$app->register('response', 'Dotink\Inkwell\Response', function($response){
		return Response::resolve($response);
	});

	$app->register('routes', 'Dotink\Inkwell\Routes');


	//
	// Include configuration logic and return the app
	//

	include 'config.php';

	return $app;
}
