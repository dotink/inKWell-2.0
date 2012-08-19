<?php namespace Dotink\Traits {

	/**
	 *
	 *
	 * @copyright Copyright (c) 2012, Matthew J. Sahagian
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 *
	 * @license Please reference the LICENSE.txt file at the root of this distribution
	 *
	 * @package Dotink\Traits
	 */

	use Dotink\Flourish;

	trait Container
	{
		/**
		 *
		 */
		private $elements = array();


		/**
		 * Sets a peer element via array access (NOT ALLOWED)
		 *
		 * @access public
		 * @param mixed $offset The element offset to set
		 * @param mixed $value The value to set for the offset
		 * @return void
		 */
		public function offsetSet($offset, $value)
		{
			throw new Flourish\ProgrammerException(
				'Cannot set element "%s", access denied',
				$offset
			);
		}


		/**
		 * Checks whether or not a element exists
		 *
		 * @access public
		 * @param mixed $offset The element offset to check for existence
		 * @return boolean TRUE if the peer exists, FALSE otherwise
		 */
		public function offsetExists($offset)
		{
			return isset($this->elements[$offset]);
		}


		/**
		 * Attempts to unset a element (NOT ALLOWED)
		 *
		 * @access public
		 * @param mixed $offset The element offset to unset
		 * @return void
		 */
		public function offsetUnset($offset)
		{
			throw new Flourish\ProgrammerException(
				'Cannot unset elements "%s", access denied',
				$offset
			);
		}


		/**
		 * Gets an element
		 *
		 * @access public
		 * @param mixed $offset The element offset to get
		 * @return void
		 */
		public function offsetGet($offset) {
			return $this->elements[$offset];
		}
	}
}
