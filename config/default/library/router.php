<?php namespace Dotink\Inkwell
{
	return Config::create(['Library'], [

		//
		// The class which we configure
		//

		'class' => __NAMESPACE__ . '\Router',

		//
		// Whether or not router runs in restless mode.  This essentially determines whether
		// or not it will redirect for matching patterns that would match if a slash was
		// added or removed
		//

		'restless' => FALSE,

		//
		// Set the word separator.
		//

		'word_separator' => '_'
	]);
}