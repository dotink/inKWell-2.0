<?php namespace Dotink\Inkwell
{
	include 'constants.php';
	include 'core.php';
	include 'functions.php';

	//
	// The IW constructor takes two arguments, although the second is optional it is shown
	// here for clarity.  The first argument is the application root by default the parent of
	// the parent of wherever this file is found.  The second argument is our base library
	// directory.  Although the structure inside the library must remain consistent, it is
	// possible to move or rename the base folder.
	//

	$app = new IW(realpath(dirname(__DIR__)), 'library');

	//
	// Register our dependencies
	//

	$app->register('config',     'Dotink\Inkwell\Config');
	$app->register('request',    'Dotink\Inkwell\Request');
	$app->register('response',   'Dotink\Inkwell\Response');
	$app->register('router',     'Dotink\Inkwell\Router');
	$app->register('scaffolder', 'Dotink\Inkwell\Scaffolder');


	//
	// Just-In-Time class aliases.  These classes will exist in the magic namespace 'App'.  So for
	// example if you register an alias of 'Text' => 'Vendor\Project\Text' it will actually need
	// to be used as 'App\Text'.
	//

	$app->alias([
		'Date'      => 'Dotink\Flourish\Date',
		'Text'      => 'Dotink\Flourish\Text',
		'Time'      => 'Dotink\Flourish\Time',
		'Timestamp' => 'Dotink\Flourish\Timestamp',
		'URL'       => 'Dotink\Flourish\URL',
		'UTF8'      => 'Dotink\Flourish\UTF8'
	]);

	//
	// Include configuration logic and return the app
	//

	include 'config.php';

	return $app;
}
