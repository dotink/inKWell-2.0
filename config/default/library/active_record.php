<?php namespace Dotink\Inkwell {

	return Config::create(['Extension', '@autoloading'], [

		//
		// The class which we configure
		//

		'class' => __NAMESPACE__ . '\ActiveRecord',

		//
		// Whether or not we should attempt to auto scaffold records using this class.
		//

		'auto_scaffold' => TRUE,

		//
		// The directory relative to application root in which user defined active record models
		// are stored.
		//

		'root_directory' => 'user/models',

		//
		// The wildcard autoloader means to use this classes __match() method and attempt to load
		// from its root directory.  Removing it will remove model autoloading.
		//

		'map' => ['*'],

	]);
}