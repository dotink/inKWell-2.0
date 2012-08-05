<?php namespace Dotink\Inkwell {

	/**
	 *
	 *
	 * @copyright Copyright (c) 2012, Matthew J. Sahagian
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 *
	 * @license Please reference the LICENSE.txt file at the root of this distribution
	 *
	 * @package Dotink\Inkwell
	 */

	use Dotink\Flourish;

	class Controller implements \ArrayAccess
	{
		use \Dotink\Traits\PeerContainer;

		public function __construct(Array $peers)
		{
			$this->peers = $peers;
		}
	}
}