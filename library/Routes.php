<?php namespace Dotink\Inkwell {

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

	use Dotink\Flourish;
	use Dotink\Interfaces;

	class Routes implements Interfaces\Routes
	{
		const CONTROLLER_INTERFACE = 'Dotink\Interfaces\Controller';


		/**
		 * The current action controller
		 *
		 * @access private
		 * @var Dotink\Interfaces\Controller
		 */
		private $actionController = NULL;


		/**
		 * A list of errors encountered when running
		 *
		 * @access private
		 * @var array
		 */
		private $errors = array();


		/**
		 * A list of links (routes => actions)
		 *
		 * @access private
		 * @var array
		 */
		private $links = array();


		/**
		 * Triggers a general ContinueException
		 *
		 * @static
		 * @access private
		 * @return void
		 */
		static private function triggerContinue()
		{
			throw new Flourish\ContinueException(
				'Error running route, continuing...'
			);
		}


		/**
		 * Construct a routes collection
		 *
		 * @access public
		 * @param array $links The links to add to the collection
		 * @return void
		 */
		public function __construct(Array $links = [])
		{
			$this->links = $links;
		}


		/**
		 * Add a link to the routes collection
		 *
		 * A link is only added if an existing link for the specified route does not exist
		 *
		 * @access public
		 * @param string $route The route key/mapping
		 * @param Closure|string $action The action to execute, callback strings are custom
		 * @return void
		 */
		public function link($route, $action)
		{
			if (!isset($this->links[route])) {
				$this->links[$route] = $action;
			}
		}


		/**
		 * "Runs" the routes collection relative to a provided request
		 *
		 * This iterates over all available links in the routes collection and attempts to match
		 * the path of the request object against each link's route.  If a match is found it will
		 * attempt to dispatch to the linked action.
		 *
		 * @access public
		 * @param Request $request The request to run against
		 * @return mixed The response of the dispatched action
		 */
		public function run(Interfaces\Request $request)
		{
			$request_uri  = $request->getPath();
			$this->errors = array();

			foreach ($this->links as $route => $action) {
				try {
					$this->actionController = NULL;

					if ($request_uri == $route) {
						$response = $this->dispatch($request, $route, $action);
					}
				} catch (ContinueException $e) {
					continue;
				} catch (YieldException $e) {
					if (isset($this->actionController)) {
						$response = $this->actionController->getError();
					} else {
						$response = $e->getMessage();
					}
					break;
				}
			}

			return $response;
		}


		/**
		 * Dispatches to a given action
		 *
		 * @access private
		 * @param Request $request The request
		 * @param string $route The route we are dispatching
		 * @param Closure|string $action The action to execute
		 * @return mixed The response of the action
		 */
		private function dispatch($request, $route, $action)
		{
			$action_elements = [
				'routes'  => $this,
				'request' => $request
			];

			if ($action instanceof \Closure) {
				//
				// Call Closures directly
				//

				return $action($action_elements);

			} elseif (is_string($action)) {
				//
				// Strings are either direct function calls or parseable object callbacks
				//

				if (strpos('::', $action) === FALSE) {
					if (!is_callable($action)) {
						$this->errors[] = sprintf(
							'Action "%s" is not callable',
							$action
						);

						self::triggerContinue();
					}

					return call_user_func($action);
				}

				list($action_class, $action_method) = self::parseAction($action);

				if (!class_exists($action_class)) {
					$this->errors[] = sprintf(
						'Action class "%s" does not exist',
						$action_class
					);

					self::triggerContinue();
				}

				if (!in_array(self::CONTROLLER_INTERFACE, class_implements($action_class))) {
					$this->errors[] = sprintf(
						'Action class "%s" does not implement %s',
						$action_class,
						self::CONTROLLER_INTERFACE
					);

					self::triggerContinue();
				}

				if (strpos('__', $action_method) === 0) {
					$this->errors[] = sprintf(
						'Action method "%s" cannot be a magic method',
						$action_method
					);

					self::triggerContinue();
				}

				if (!method_exists($action_class, $action_method)) {
					$this->errors[] = sprintf(
						'Action method "%s" does not exist for class %s',
						$action_method,
						$action_class
					);

					self::triggerContinue();
				}

				$this->actionController = new $action_class($action_elements);

				if (!is_callable([$controller, $action_method])) {
					$this->errors[] = sprintf(
						'Action method "%s" is not callable on object of class %s',
						$action_method,
						$action_class
					);

					self::triggerContinue();
				}

				return $this->actionController->$action_method();

			} else {
				//
				// Invalid action type
				//

				$this->errors[] = sprintf(
					'Invalid action "%s" for route %s',
					$action,
					$route
				);

				self::triggerContinue();
			}
		}
	}
}
