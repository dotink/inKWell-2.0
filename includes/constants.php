<?php namespace Dotink\Inkwell
{
	//
	// UTILITY SHORTHANDS
	//

	define(__NAMESPACE__ . '\LB', PHP_EOL);
	define(__NAMESPACE__ . '\DS', DIRECTORY_SEPARATOR);

	//
	// HTTP METHODS
	//

	define(__NAMESPACE__ . '\HTTP\GET',    'get');
	define(__NAMESPACE__ . '\HTTP\POST',   'post');
	define(__NAMESPACE__ . '\HTTP\PUT',    'put');
	define(__NAMESPACE__ . '\HTTP\DELETE', 'delete');
	define(__NAMESPACE__ . '\HTTP\HEAD',   'head');

	//
	// REGULAR EXPRESSIONS
	//

	define(__NAMESPACE__ . '\REGEX\ABSOLUTE_PATH', '#^(/|\\\\|[a-z]:(\\\\|/)|\\\\|//)#i');
}

