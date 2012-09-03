<?php namespace Dotink\Inkwell
{
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
	use Dotink\Interfaces;
	use Dotink\Traits;

	class Controller implements Interfaces\Inkwell, Interfaces\Controller
	{
		//
		// Add array access implementation for the "elements" property
		//

		use Traits\Container;


		/**
		 *
		 */
		final public function __construct(Array $elements)
		{
			$this->elements = $elements;
		}


		/**
		 *
		 */
		public function getError()
		{
			return $this->error;
		}


		/**
		 *
		 */
		protected function triggerError($error = 'not_found')
		{
			$this->error = 'FU!';
			throw new Flourish\YieldException(
				'Controller has yielded due to error: %s',
				$error
			);
		}
	}
}
