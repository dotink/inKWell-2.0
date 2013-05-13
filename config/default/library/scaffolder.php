<?php namespace Dotink\Inkwell
{
	return Config::create(['Library'], [

		//
		// The class which we configure
		//

		'class' => __NAMESPACE__ . '\Scaffolder',

		//
		// Where are we looking for our scaffolding templates?
		//

		'root_directory' => 'user/scaffolding',

		//
		// The scaffolder scope contains generic information relevant to scaffolding PHP code.
		//

		'scope' => [

			//
			// Used for namespacing by default
			//

			'vendor' => 'Vendor',
			'module' => 'Project',

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