<?php namespace Dotink\Inkwell
{
	include 'constants.php';
	include 'core.php';
	include 'functions.php';

	$lib_root = isset($_SERVER['IW_LIB_ROOT'])
		? $_SERVER['IW_LIB_ROOT']
		: (
			isset($_ENV['IW_LIB_ROOT'])
				? $_ENV['IW_LIB_ROOT']
				: 'library'
		);

	//
	// The IW constructor takes two arguments, although the second is optional it is shown
	// here for clarity.  The first argument is the application root by default the parent of
	// the parent of wherever this file is found.  The second argument is our base library
	// directory.  Although the structure inside the library must remain consistent, it is
	// possible to move or rename the base folder.
	//

	$app = new IW(realpath(dirname(__DIR__)), $lib_root);

	//
	// Register our dependencies
	//

	$app->register('config',     'Dotink\Inkwell\Config');
	$app->register('request',    'Dotink\Inkwell\Request');
	$app->register('response',   'Dotink\Inkwell\Response');
	$app->register('router',     'Dotink\Inkwell\Router');
	$app->register('scaffolder', 'Dotink\Inkwell\Scaffolder');

	//
	// Include configuration logic and return the app
	//

	include 'config.php';

	return $app;
}
