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
		use Traits\ActionCaller;


		/**
		 * The application instance which loaded this controller
		 *
		 * @static
		 * @access private
		 * @var Dotink\Inkwell\IW
		 */
		static private $app = NULL;


		/**
		 * Initialize the class
		 *
		 * @static
		 * @access public
		 * @param Dotink\Inkwell\IW $app The application instance loading the class
		 * @param array $config The configuration array for the class
		 * @return boolean TRUE on success, FALSE on failure
		 */
		static private function __init($app, Array $config = array())
		{
			self::$app = $app;

			return TRUE;
		}


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
			$allowed_methods = array_map('strtoupper', !is_array($allowed_method)
				? func_get_args()
				: $allowed_method
			);

			if (!in_array($current_method, $allowed_methods)) {
				$this['response']->setHeader('Allow', $allowed_methods);
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


		/**
		 *
		 */
		protected function exec($url, $request)
		{
			$action   = $this['router']->resolve($url, $request);
			$response = new App\Response();

			ob_start();

			$action_response   = self::callAction($this['router'], $action, $request, $response);
			$resolved_response = ($output = ob_get_clean())
				? $response(HTTP\OK, NULL, [], $output)
				: $response->resolve($action_response);

			//
			// transform response view
			//

			return $resolved_response;
		}
	}
}
