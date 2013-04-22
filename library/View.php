<?php namespace Dotink\Inkwell
{
	use App;
	use Dotink\Flourish;
	use Dotink\Interfaces;

	/**
	 * View Class
	 *
	 * @copyright Copyright (c) 2012, Matthew J. Sahagian
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 *
	 * @license Please reference the LICENSE.txt file at the root of this distribution
	 *
	 * @package Dotink\Inkwell
	 */
	class View implements Interfaces\Inkwell
	{

		/**
		 * The view root directory
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $viewRoot = NULL;

		/**
		 * Initialize the class
		 *
		 * @param Dotink\Inkwell\IW $app The application instance loading the class
		 * @param array $config The configuration array for the class
		 * @return boolean TRUE on success, FALSE on failure
		 */
		static public function __init($app, Array $config = array())
		{
			self::$viewRoot = $app->getRoot(__CLASS__);
		}


		public function make()
		{

		}
	}
}