<?php namespace Dotink\Inkwell
{
	//
	// Route maps take the following format:
	//
	// route => action
	//
	// The route is a URL string with some components replaced tokens, for example:
	//
	// /articles/[!:slug]
	//
	// Tokens are deliminted by square brackets ('[]') and contain two parts separated by a
	// colon (':').  The first part, preceding the colon, is a pattern or pattern identifier while
	// the second part is a parameter name to map to:
	//
	// [<pattern>:<parameter name>]
	//
	// The <pattern> can be one of the following symbols which are predefined, or, if surrounded
	// by parenthesis a regular expression:
	//
	// ! = Any character except a forward slash
	// # = An integer with optional minus sign in front for negative values
	// % = A float, with optional minus sign in front for negative values
	// $ = A valid PHP variable or class name
	// * = All characters, including forward slash (used for trailing wildcards)
	//
	// Example:
	//
	// /map/[%:longitude]/[%:latitude]
	//
	// Match URLs:
	//
	// /map/0.3509/2.2742
	// /map/-3.2357/45.2352
	//
	// Example:
	//
	// /articles/[#:year]-[#:month]-[#:day]/[!:slug]
	//
	// Match URLs:
	//
	// /articles/2009-08-17/how_i_met_your_mother
	//
	// The pattern can optionally be a regular expression if it is wrapped in parenthesis, however
	// if you need to group internal patterns be sure to specify a non-capturing set.
	//
	// The parameter name is allowed to contain any character except for the ':' and ']'.  Although
	// you should probably be sane with it.
	//
	// The action (i.e. the value in the key/value pair), can be a valid PHP function name,
	// closure, class::method string callback, or array callback.  It is important to note that
	// classes will be instantiated by the router prior to calling the method, even if the string
	// callback takes on the form of a static method.
	//

	return Config::create('Core', [
		'/' => 'phpinfo',
		'/hello/[!:name]' => 'Mattsah\Test\TestController::hello',
		'/[$:class]/[$:method]/[!:wash]' => '[uc:class]::[lc:method]',
	]);
}
