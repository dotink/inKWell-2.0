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

		'HTTP\GET'    => 'GET',
		'HTTP\POST'   => 'POST',
		'HTTP\PUT'    => 'PUT',
		'HTTP\DELETE' => 'DELETE',
		'HTTP\HEAD'   => 'HEAD',

		//
		// HTTP RESPONSES
		//

		'HTTP\OK'             => 'Ok',
		'HTTP\CREATED'        => 'Created',
		'HTTP\ACCEPTED'       => 'Accepted',
		'HTTP\NO_CONTENT'     => 'No Content',
		'HTTP\BAD_REQUEST'    => 'Bad Request',
		'HTTP\NOT_AUTHORIZED' => 'Not Authorized',
		'HTTP\FORBIDDEN'      => 'Forbidden',
		'HTTP\NOT_FOUND'      => 'Not Found',
		'HTTP\NOT_ALLOWED'    => 'Not Allowed',
		'HTTP\NOT_ACCEPTABLE' => 'Not Acceptable',
		'HTTP\SERVER_ERROR'   => 'Internal Server Error',
		'HTTP\UNAVAILABLE'    => 'Service Unavailable',

		//
		// HTTP REDIRECTS
		//

		'HTTP\REDIRECT_PERMANENT' => 301,
		'HTTP\REDIRECT_FOUND'     => 302, // Redirects with get method, assuming processing done
		'HTTP\REDIRECT_SEE_OTHER' => 303,
		'HTTP\REDIRECT_TEMPORARY' => 307, // Redirects with original method, assuming nothing done

		//
		// CACHE TYPES
		//

		'CACHE\PUBLIC'   => 'public',
		'CACHE\PRIVATE'  => 'private',
		'CACHE\NO_STORE' => 'no-store',

		//
		// REGULAR EXPRESSIONS
		//

		'REGEX\ABSOLUTE_PATH' => '#^(/|\\\\|[a-z]:(\\\\|/)|\\\\|//)#i',

		//
		// INKWELL EXECUTION MODES
		//

		'EXEC_MODE_DEVELOPMENT' => 'development',
		'EXEC_MODE_PRODUCTION'  => 'production'

	] as $constant => $value) {

		//
		// All constants defined in the array above will be part of the Dotink\Inkwell namespace
		//

		define(__NAMESPACE__ . '\\' . $constant, $value);
	}
}

