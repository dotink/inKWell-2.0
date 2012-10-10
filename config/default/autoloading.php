<?php namespace Dotink\Inkwell
{
	return Config::create(['Core'], [

		//
		// Autoloading standards allow you to register transformation functions or methods
		// which will take a class name and return it transformed into a path of whatever
		// style you like.  For example, PSR-0 would take the class name and transform it
		// in the following manner:
		//
		// Class: Vendor\PackageName\Old_Style_Class.php
		// Path:  Vendor/PackageName/Old/Style/Class.php
		//
		// The inKWell Autoloading standard would do the following:
		//
		// Path: vendor/package_name/Old_Style_Class.php
		//
		// The inKWell loading standard is used commonly across extensions.  We encourage
		// flattened namespaces and vary our base directories.  So, although, not registered here,
		// for example, various components will load from the following paths:
		//
		// Ex. (General):  user/<component type>/<underscorized namespace>/Example.php
		// Ex. (Specific): user/controllers/dotink/forums/ForumsController.php
		// Ex. (Specific): user/models/dotink/kwiki/Page.php
		//

		'standards' => [
			'IW'   => __NAMESPACE__ . '\IW::transformClassToIW',
			'PSR0' => __NAMESPACE__ . '\IW::transformClassToPSR0'
		],

		'map' => [
			'vendor'  => 'PSR0: vendor'
		]
	]);
}
