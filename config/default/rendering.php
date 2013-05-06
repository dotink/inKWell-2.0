<?php namespace Dotink\Inkwell
{
	return Config::create(['Core'], [

		//
		// Filters are run before rendering methods and will attempt to use standard means to
		// resolve a view based on a requested type.  For example, if the type is
		// 'application/json', a filter might see if the view is an object, and if so, see if it
		// is JSONSerializable.  If it's a string, it might attempt to json_decode it to validate
		// it, and if not, JSON encode it.  If it's some other variable, it might simply JSON
		// encode it.
		//

		'filters' => [

			//
			// There are no built in filters by default and should be added depending on the
			// types you intend to support.
			//

		],

		//
		// The methods key contains a list of classes and the respective method which should
		// be called on objects of that class in order to render their output to a string.
		//

		'methods' => [
			'Dotink\Inkwell\View'   => 'make',
			'Dotink\Flourish\Image' => 'output'
		],

	]);
}
