<?php namespace Dotink\Inkwell
{
	//
	// This file contains constants defined as part of inKWell.  You can add your own constants
	// here, but be aware of namespacing.
	//

	foreach ([

		//
		// UTILITY SHORTHANDS
		//

		'LB' => PHP_EOL,
		'DS' => DIRECTORY_SEPARATOR,

		//
		// HTTP METHODS
		//

		'HTTP\GET'    => 'get',
		'HTTP\POST'   => 'post',
		'HTTP\PUT'    => 'put',
		'HTTP\DELETE' => 'delete',
		'HTTP\HEAD'   => 'head',

		//
		// REGULAR EXPRESSIONS
		//

		'REGEX\ABSOLUTE_PATH' => '#^(/|\\\\|[a-z]:(\\\\|/)|\\\\|//)#i',

	] as $constant => $value) {

		//
		// All constants defined in the array above will be part of the Dotink\Inkwell namespace
		//

		define(__NAMESPACE__ . '\\' . $constant, $value);
	}
}

