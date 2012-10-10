<?php namespace <%= self::getAppInfo('vendor', TRUE) %>\<%= self::getAppInfo('name', TRUE) %>
{
	/**
	 * The <%= self::getInfo('class') %> class.
	 *
	 * @copyright Copyright (c) <%= date('Y') %>, <%= self::getAppInfo('copyright') %>
	 * @author <%= self::getAppInfo('author') %> [<%= self::getAppInfo('tag') %>] <<%= self::getAppInfo('email') %>>
	 *
	 * @license Please reference the LICENSE.txt file at the root of this distribution
	 * @dependency Dotink\Inkwell\Controller
	 */

	use Dotink\Inkwell;
	use Dotink\Flourish;
	use Dotink\Interfaces;

	class <%= self::getInfo('class', TRUE) %> extends Inkwell\Controller
	{
		/**
		 * Initialize the class
		 *
		 * @param Dotink\Inkwell\IW $app The application instance loading the class
		 * @param array $config The configuration data for this class
		 * @return boolean TRUE on success, FALSE on failure
		 */
		static public function __init($app, Array $config = array())
		{
			//
			// Initialization logic for the parent class
			//

			return TRUE;
		}
	}
}
