<?php namespace Dotink\Inkwell
{
	return Config::create(['Core'], [

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
