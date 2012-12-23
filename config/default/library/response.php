<?php namespace Dotink\Inkwell
{
	return Config::create(['Library'], [

		//
		// Renderers are custom callback logic which will have the response passed to them
		// prior to outputting the view.  They are based on mime-type and will only be called
		// if the response content type matches.
		//
		// The match, represented by the key of the array, is actually a RegEx delimited by #.
		//
		// Each callback is rendered in the order in which it is defined.
		//

		'renderers' => [],

		//
		// Response state are short name aliases for various response codes and/or default content.
		// They should not include redirects, as redirects are never as an actual bodied response
		// and are handled by the Request class.
		//

		'states' => [

			//
			// For additional information about when each one of these response codes should be
			// used, please see the following:
			//
			// http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
			//

			HTTP\OK => [
				'code' => 200,
				'body' => NULL
			],

			HTTP\CREATED => [
				'code' => 201,
				'body' => NULL
			],

			HTTP\ACCEPTED => [
				'code' => 202,
				'body' => NULL
			],

			HTTP\NO_CONTENT => [
				'code' => 204,
				'body' => NULL
			],

			HTTP\BAD_REQUEST => [
				'code' => 400,
				'body' => 'The request could not be understood'
			],

			HTTP\NOT_AUTHORIZED => [
				'code' => 401,
				'body' => 'The requested resource requires authorization'
			],

			HTTP\FORBIDDEN => [
				'code' => 403,
				'body' => 'You do not have permission to view the requested resource'
			],

			HTTP\NOT_FOUND => [
				'code'    => 404,
				'body'    => 'The requested resource could not be found'
			],

			HTTP\NOT_ALLOWED => [
				'code' => 405,
				'body' => 'The requested resource does not support this method'
			],

			HTTP\NOT_ACCEPTABLE => [
				'code' => 406,
				'body' => 'The requested resource is not available in the accepted parameters'
			],

			HTTP\SERVER_ERROR => [
				'code' => 500,
				'body' => 'The requested resource is not available due to an internal error'
			],

			HTTP\UNAVAILABLE => [
				'code' => 503,
				'body' => 'The requested resource is temporarily unavailable'
			]
		],

	]);
}
