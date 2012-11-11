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
		use Traits\Container;

		/**
		 *
		 */
		protected function acceptTypes($accept_types)
		{
			if (!is_array($accept_types)) {
				$accept_types = func_get_args();
			}


		}


		/**
		 *
		 */
		protected function allowMethods($allowed_methods)
		{
			if (!is_array($allowed_methods)) {
				$allowed_methods = func_get_args();
			}

			$allowed_methods = array_map('strtolower', $allowed_methods);
			$current_method  = $this['request']->getMethod();

			if (!in_array($current_method, $allowed_methods)) {
				$this['response']->setHeader('Allow', implode(', ', $allowed_methods));
				$this->triggerError('not_allowed');
			}

			return $current_method;
		}


		/**
		 * Check whether or not a given class is the entry controller
		 *
		 * @access protected
		 * @param string $class The class to check
		 */
		protected function checkEntry($class)
		{
			return $this['routes']->checkEntryAction($class);
		}


		/**
		 * Check whether or not a given class and method are the entry controller and action
		 *
		 * If a only a single argument is given it will be assumed to be the method, and the
		 * class will be determined from the current instance.
		 *
		 * @access protected
		 * @param string $class The class to check
		 * @param string $method The method to check
		 * @return boolean TRUE if the parameters match the router's entry and action
		 */
		protected function checkEntryAction($class, $method = NULL)
		{
			if (func_num_args == 1) {
				$class  = get_class($this);
				$method = func_get_arg(0);
			}

			return $this['routes']->checkEntryAction($class, $method);
		}


		/**
		 * Executes a sub request
		 *
		 * @access protected
		 * @return Response
		 */
		protected function exec($method, $type, $url, $params)
		{
			$url = $this['routes']->compose($url, $params, $params);

			if ($url[0] = '/') {
				$request_class  = get_class($this['request']);
				$request        = new $request_class($method, $type, $url, $params);
				$response       = clone $this['response'];

				return $this['routes']->run($request, $response);
			}


			$request_class = get_class($this['request']);
			$request       = new Request();


			if ($url[0] = '/') {

			}
		}


		/**
		 *
		 */
		protected function triggerError($error, $headers = array(), $message = NULL)
		{
			$this['response']($error, $message);

			throw new Flourish\YieldException(
				'Controller has yielded due to error: %s',
				$error
			);
		}
	}
}
