<?php namespace Dotink\Inkwell
{
	return Config::create(['Library'], [

		//
		// The class which we configure
		//

		'class' => __NAMESPACE__ . '\Scaffolder',

		//
		// Preload our scaffolder
		//

		'preload' => TRUE,

		//
		// If the scaffolder is disabled, this will only prevent on the fly
		// scaffolding from taking place... you will still be able to scaffold
		// by manually calling $app['scaffolder']->build()
		//

		'disabled' => FALSE,

		//
		// Where are we looking for our scaffolding templates?
		//

		'root_directory' => 'user/scaffolding',

		//
		// Application info will be available inside a template using self::getAppInfo()
		//

		'app_info' => [

			//
			// Used for namespacing by default
			//

			'vendor' => 'Vendor',
			'name'   => 'Project',

			//
			// Author, Tag (initials or a handle), email, copyright
			//

			'author'    => 'Jamie Doe',
			'copyright' => 'Acme, Inc.',
			'tag'       => 'user',
			'email'     => 'info@dotink.org',
		]

	]);
}