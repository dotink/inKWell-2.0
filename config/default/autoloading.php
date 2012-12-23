<?php namespace Dotink\Inkwell
{
	return Config::create(['Core'], [

		//
		// Autoloading standards allow you to register transformation functions or methods
		// which will take a class name and return it transformed into a path of whatever
		// style you like.
		//

		'standards' => [

			//
			// The inKWell loading standard is used commonly across the framework and extensions by
			// default.  It encourages flattened namespaces and varied base directories.
			//
			// You can read more about it here:
			//
			// http://inkwell.dotink.org/2.0/architecture/autoloader#standard
			//
			// It's translations look something like the following:
			//
			// Class: Vendor\PackageName\Old_Style_Class.php
			// Path: vendor/package_name/Old_Style_Class.php
			//

			'IW'   => __NAMESPACE__ . '\IW::transformClassToIW',

			//
			// PSR-0 is well enough documented here:
			//
			// https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
			//
			// The translation looks something like the following:
			//
			// Class: Vendor\PackageName\Old_Style_Class.php
			// Path:  Vendor/PackageName/Old/Style/Class.php
			//

			'PSR0' => __NAMESPACE__ . '\IW::transformClassToPSR0'
		],

		//
		// The loader map identifies methods for matching class names (the key) and potential
		// target directories (the value) where those classes can be loaded from, optionally
		// prepended by a loading standard.
		//
		// 'match' => 'Standard: directory'
		//
		// If the standard is ommitted, the default will be to use the compatibility standard which
		// will use only the class name without the namespace of the loading class.
		//

		'map' => [

			'vendor'  => 'PSR0: vendor'

		]
	]);
}
