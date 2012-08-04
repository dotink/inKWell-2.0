<?php

	/**
	 * Routes class responsible for mapping request paths to logic.
	 *
	 * @copyright Copyright (c) 2012, Matthew J. Sahagian
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 *
	 * @license Please reference the LICENSE.txt file at the root of this distribution
	 *
	 * @package Dotink\Inkwell
	 */

	namespace Dotink\Inkwell;

	use Dotink\Flourish;

	class Routes
	{
		private $map = array();

		public function __construct()
		{
			$this->map = ['any' => [], 'get' => [], 'put' => [], 'post' => [], 'delete' => []];
		}

		public function __get($name)
		{
			if (!in_array($name, array_keys($this->map))) {
				throw new Flourish\ProgrammerException(
					'Cannot add route to "%s", invalid method name',
					$name
				);
			}

			return $map =& $this->map[$name];
		}

		public function run($request)
		{
			return 0;
		}
	}