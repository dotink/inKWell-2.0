<?php namespace Dotink\Interfaces
{
	/**
	 * The Dotink Routes interface
	 *
	 * @copyright Copyright (c) 2012, Matthew J. Sahagian
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 *
	 * @license Please reference the LICENSE.txt file at the root of this distribution
	 *
	 * @package Dotink\Interfaces
	 */

	interface Routes
	{
		public function link($route, $action);
	}
}