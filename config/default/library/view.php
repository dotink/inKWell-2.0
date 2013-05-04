<?php namespace Dotink\Inkwell
{
	return Config::create(['Library'], [

		//
		// The class which we configure
		//

		'class' => __NAMESPACE__ . '\View',

		//
		// The directory relative to the inKWell root directory in which views
		// are stored.  Using the set() or add() method on a view will prepend
		// this directory if your view does not begin with a slash.
		//
		// Example:
		//
		// View::create('html.php');
		//
		// Resolves load the view:
		//
		// <inkwell_application_root>/<view_root>/html.php
		//

		'root_directory' => 'user/views',

		//
		// A directory containing helper functions to be included for matching types.  If the
		// type of the primary template is 'html', it will attempt to load the file
		// <helper_directory>/html.php -- this is relative to the application root
		//

		'helper_directory' => 'library/helpers/view',

		//
		// The extension map contains a list of extensions and the type of extension they compile
		// to.  This is used to determine which types of files can ultimately be combined and will
		// be merged with the built in map.  You shouldn't need to touch this too much unless
		// you want some seriously custom assetic filters. or pre-processing support.
		//

		'extension_map' => [],

		//
		// The cache mode can be any valid inKWell execution mode, and is the same if left as
		// NULL.  If the cache_mode equals EXEC_MODE_DEVELOPMENT then cached asset files will be
		// reubilt if any included file was modified later than the cache file.  If it's
		// EXEC_MODE_PRODUCTION then they will only be rebuilt if they're missing.
		//

		'cache_mode' => NULL,

		//
		// The cache directory is relative to the document root and is used to store cached
		// versions of various assets post-preprocessing.
		//

		'cache_directory' => 'assets/cache',

		//
		// You can add assetic compatible filter classes to the lists below.  Add a new key and
		// an array of filters to support various languages (CoffeeScript, Less, etc).
		//
		// NOTE: Assetic filters by themselves may have additional dependencies.
		//

		'asset_filters' => [
			'css' => [],
			'js'  => []
		]

	]);
}