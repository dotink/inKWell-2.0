<?php namespace Dotink\Inkwell
{
	return Config::create(['Model'], [
		'class' => 'App\Test\Customer',

		'auto_map' => 'customers'
	]);
}
