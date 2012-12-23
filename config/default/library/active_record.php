<?php namespace Dotink\Inkwell
{
	return Config::create(['Library'], [

		//
		// Whether or not we should attempt to auto scaffold records using this class when in
		// development mode.
		//

		'auto_scaffold' => TRUE,

		//
		// The directory relative to application root in which user defined active record models
		// are stored.
		//

		'root_directory' => 'user/models',

	]);
}