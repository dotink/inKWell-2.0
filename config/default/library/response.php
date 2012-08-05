<?php namespace Dotink\Inkwell;

	return Config::create(['Library', '@autoloading'], [

		//
		// The default response
		//

		'default' => 'not_found',

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
		// Responses are short name aliases for various response codes.  They should not include
		// redirects, as redirects are never as an actual bodied response and are handled by
		// Controller::redirect().
		//

		'responses' => [

			//
			// For additional information about when each one of these response codes should be
			// used, please see the following:
			//
			// http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
			//

			'ok' => [
				'code' => 200,
				'body' => NULL
			],

			'created' => [
				'code' => 201,
				'body' => NULL
			],

			'accepted' => [
				'code' => 202,
				'body' => NULL
			],

			'no_content' => [
				'code' => 204,
				'body' => NULL
			],

			'bad_request' => [
				'code' => 400,
				'body' => 'The requested could not be understood'
			],

			'not_authorized' => [
				'code' => 401,
				'body' => 'The requested resource requires authorization'
			],

			'forbidden' => [
				'code' => 403,
				'body' => 'You do not have permission to view the requested resource'
			],

			'not_found' => [
				'code' => 404,
				'body' => 'The requested resource could not be found'
			],

			'not_allowed' => [
				'code' => 405,
				'body' => 'The requested resource does not support this method'
			],

			'not_acceptable' => [
				'code' => 406,
				'body' => 'The requested resource is not available in the accepted parameters'
			],

			'internal_server_error' => [
				'code' => 500,
				'body' => 'The requested resource is not available due to an internal error'
			],

			'service_unavailable' => [
				'code' => 503,
				'body' => 'The requested resource is temporarily unavailable'
			]
		],

		//
		// Load supported response types from includes/lib/responses
		//

		'@autoloading' => [
			'map' => ['Response*' => 'includes/lib/responses']
		]
	]);
