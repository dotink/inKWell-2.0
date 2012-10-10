<?php namespace Dotink\Inkwell
{
	//
	// This file is responsible for running a request, sending the response, and returning
	// the status.
	//

	$request  = $app->create('request',  'Dotink\Interfaces\Request');
	$response = $app->run($request);

	return $request->checkMethod(HTTP\HEAD)

		//
		// Return only the headers
		//

		? $response->send(TRUE)

		//
		// Return the full headers and body
		//

		: $response->send();
}