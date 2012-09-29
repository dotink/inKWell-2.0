<?php namespace Dotink\Inkwell
{
	return Config::create(['Library', '@autoloading'], [

		//
		// The class which we configure
		//

		'class' => __NAMESPACE__ . '\Controller',

		//
		// Whether or not we should attempt to auto scaffold controllers using this class.
		//

		'auto_scaffold' => FALSE,

		//
		// The directory relative to application root in which user defined controllers are stored.
		//

		'root_directory' => 'user/controllers',

		//
		// The wildcard autoloader means to use this classes __match() method and attempt to load
		// from its root directory.  Removing it will remove controller autoloading.
		//

		'map' => ['*'],

		//
		// The default accept types in preferred order.
		//

		'default_accept_types' => [
			'text/plain',
			'text/html'
		]
	]);
}
