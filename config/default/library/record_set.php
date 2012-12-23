<?php namespace Dotink\Inkwell
{
	return Config::create(['Library', '@autoloading'],  [

		//
		// Whether or not we should attempt to auto scaffold record sets using this class when
		// in development mode.
		//

		'auto_scaffold' => TRUE,

		//
		// The directory relative to application root in which user defined record sets are stored.
		//

		'root_directory' => 'user/models/sets'
	]);
}
