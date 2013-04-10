<?php namespace Dotink\Inkwell
{
	use App;
	use Dotink\Flourish;
	use Dotink\Interfaces;

	/**
	 * The inKWell Scaffolder
	 *
	 * The scaffolder class is a lightweight "templating" class designed to allow you to template
	 * php easily and without some of the normal pitfalls associated with templating PHP with PHP.
	 * Additionally, it has a few helper methods for cleaning up variables and validating variable
	 * names as well as the primary make and build methods.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 * @copyright Copyright (c) 2012, Matthew J. Sahagian
	 * @license Please reference the LICENSE.txt file at the root of this distribution
	 *
	 * @package Dotink\Inkwell
	 */
	class Scaffolder
	{
		/**
		 * Initialize the class
		 *
		 * @param Dotink\Inkwell\IW $app The application instance loading the class
		 * @param array $config The configuration array for the class
		 * @return boolean TRUE on success, FALSE on failure
		 */
		static public function __init($app, Array $config = array())
		{
		}
	}
}