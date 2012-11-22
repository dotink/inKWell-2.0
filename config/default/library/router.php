<?php namespace Dotink\Inkwell
{
	return Config::create(['Library'], [

		//
		// Whether or not router runs in restless mode.  This essentially determines whether
		// or not it will redirect for matching patterns that would match if a slash was
		// added or removed
		//

		'restless' => FALSE
	]);
}