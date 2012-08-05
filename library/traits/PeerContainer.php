<?php

	namespace Dotink\Traits;
	use       Dotink\Flourish;

	trait PeerContainer
	{
		/**
		 *
		 */
		private $peers = array();


		/**
		 * Sets a peer element via array access (NOT ALLOWED)
		 *
		 * @access public
		 * @param mixed $offset The peer element offset to set
		 * @param mixed $value The value to set for the offset
		 * @return void
		 */
		public function offsetSet($offset, $value)
		{
			throw new Flourish\ProgrammerException(
				'Cannot set peer "%s", access denied',
				$offset
			);
		}


		/**
		 * Checks whether or not a peer element exists
		 *
		 * @access public
		 * @param mixed $offset The peer element offset to check for existence
		 * @return boolean TRUE if the peer exists, FALSE otherwise
		 */
		public function offsetExists($offset)
		{
			return isset($this->peers[$offset]);
		}


		/**
		 * Attempts to unset a peer element (NOT ALLOWED)
		 *
		 * @access public
		 * @param mixed $offset The peer element offset to unset
		 * @return void
		 */
		public function offsetUnset($offset)
		{
			throw new Flourish\ProgrammerException(
				'Cannot unset peer "%s", access denied',
				$offset
			);
		}


		/**
		 * Gets a peer element
		 *
		 * @access public
		 * @param mixed $offset The peer element offset to get
		 * @return void
		 */
		public function offsetGet($offset) {
			return $this->peers[$offset];
		}
	}