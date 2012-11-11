<?php namespace Dotink\Inkwell
{
	return Config::create(['Library', '@autoloading'],  [

		//
		// The class which we configure
		//

		'class' => __NAMESPACE__ . '\RecordSet',

		//
		// Whether or not we should attempt to auto scaffold record sets using this class.
		//

		'auto_scaffold' => TRUE,

		//
		// The directory relative to application root in which user defined record sets are stored.
		//

		'root_directory' => 'user/models/sets'
	]);
}
