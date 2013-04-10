<?php namespace Dotink\Inkwell
{
	return Config::create(['Library'], [

		//
		// The class which we configure
		//

		'class' => __NAMESPACE__ . '\Controller',

		//
		// Whether or not we should attempt to auto scaffold controllers using this class when
		// in development mode.
		//

		'auto_scaffold' => FALSE,

		//
		//
		//

		'auto_load' => TRUE,

		//
		// The directory relative to application root in which user defined controllers are stored.
		//

		'root_directory' => 'user/controllers',

		//
		// The default accept types in preferred order.
		//

		'default_accept_types' => [
			'text/plain',
			'text/html'
		]
	]);
}
