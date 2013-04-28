<?php namespace Dotink\Inkwell
{
	use App;
	use Dotink\Flourish;
	use Dotink\Interfaces;
	use Dotink\Traits;

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
	class Controller implements Interfaces\Inkwell, Interfaces\Controller
	{
		use Traits\Container;

		/**
		 * Determines whether or not a class name is a controller
		 *
		 * @static
		 * @access public
		 * @param string $class The class name to match
		 * @return boolean TRUE if the class name matches, FALSE otherwise
		 */
		static public function __match($class)
		{
			return preg_match('/(.*)Controller$/', $class);
		}


		/**
		 * Restricts the acceptable mime types for a given action.
		 *
		 * Optionally this method can accept an array of acceptable mime types instead of separate
		 * parameters.
		 *
		 * @throws YieldException When there are now acceptable types which match the Accept header
		 *
		 * @access protected
		 * @param string $accept_type A type which is acceptable
		 * @param ...
		 * @return string The current best accept type
		 */
		protected function acceptTypes($accept_type)
		{
			$accept_types = !is_array($accept_type)
				? func_get_args()
				: $accept_type;

		}


		/**
		 * Restricts the allowed methods for a given action.
		 *
		 * Optionally this method can accept an array of allowed methods instead of separate
		 * parameters.
		 *
		 * @throws YieldException When there are no allowed methods which match the current method
		 *
		 * @access protected
		 * @param string $allowed_method A method which is allowed
		 * @param ...
		 * @return string The current method for the request
		 */
		protected function allowMethods($allowed_method)
		{
			$current_method  = $this['request']->getMethod();
			$allowed_methods = !is_array($allowed_method)
				? func_get_args()
				: $allowed_method;

			if (!in_array($current_method, array_map('strtolower', $allowed_methods))) {
				$this['response']->setHeader('Allow', implode(', ', $allowed_methods));
				$this->triggerError(HTTP\NOT_ALLOWED);
			}

			return $current_method;
		}


		/**
		 * Check whether or not a given class is the entry controller
		 *
		 * @access protected
		 * @param string $class The class to check
		 * @return boolean TRUE if the entry point maches the class, FALSE otherwise
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
		 * @return boolean TRUE if the entry point matches the class and method, FALSE otherwise
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
		}


		/**
		 * Triggers a controller error by throwing a YieldException
		 *
		 * @access protected
		 * @param string $error The error to trigger
		 * @param array $headers The headers to pass in the response
		 * @param string $message The body of the response
		 * @return void
		 */
		protected function triggerError($error, $headers = array(), $message = NULL)
		{
			if (func_num_args() == 2) {
				$message = func_get_arg(1);
				$headers = array();
			}

			$this['response']($error, NULL, $headers, $message);

			throw new Flourish\YieldException(
				'Controller has yielded due to error: %s',
				$error
			);
		}
	}
}
