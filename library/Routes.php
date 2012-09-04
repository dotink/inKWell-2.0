<?php namespace Dotink\Inkwell
{
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
		const REGEX_TOKEN          = '/\[[^\]]*\]/';


		/**
		 * A list of regex patterns for various pattern tokens
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $patterns = [
			'#' => '([-]?(?:[0-9]+))',
			'%' => '([-]?[0-9]+\.[0-9]+',
			'!' => '([^/]*)',
			'$' => '([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)',
			'*' => '(.*)'
		];


		/**
		 * The current action controller
		 *
		 * @access private
		 * @var Interfaces\Controller
		 */
		private $controller = NULL;


		/**
		 * A list of errors, warnings, and notices encountered when running
		 *
		 * @access private
		 * @var array
		 */
		private $log = array();


		/**
		 * A list of links mapping route patterns to route information
		 *
		 * @access private
		 * @var array
		 */
		private $links = array();


		/**
		 * A list of redirects mapping route patterns to redirect information
		 *
		 * @access private
		 * @var array
		 */
		private $redirects = array();


		/**
		 * Compiles a route, replacing valid tokens with match patterns
		 *
		 * @static
		 * @access private
		 * @param string $route The route to compile
		 * @return array An array containing the final compiled pattern and parameter names
		 */
		static private function compile($route)
		{
			$params = array();

			if (preg_match_all(self::REGEX_TOKEN, $route, $matches)) {
				foreach ($matches[0] as $i => $token) {
					$holder = '%TOKEN' . $i . '%';
					$route  = str_replace($token, $holder, $route);
				}

				$route = preg_quote($route, '#');

				foreach ($matches[0] as $i => $token) {
					$split_pos = strrpos($token, ':');
					$params[]  = trim(substr($token, $split_pos + 1, -1));
					$pattern   = trim(substr($token, 1, $split_pos - 1));
					$holder    = '%TOKEN' . $i . '%';

					if (isset(self::$patterns[$pattern])) {
						$route = str_replace($holder, self::$patterns[$pattern], $route);
					} elseif ($pattern[0] == '(' && $pattern[strlen($pattern) - 1] == ')') {
						$route = str_replace($holder, $pattern, $route);
					} else {
						throw new Flourish\ProgrammerException(
							'Invalid complitation pattern %s',
							$pattern
						);
					}
				}
			}

			return [$route, $params];
		}


		/**
		 * Decompiles a route, replacing valid tokens with parameter values
		 *
		 * @static
		 * @access private
		 * @param string $route The route to decompile
		 * @param array $params An associative array of param names => values
		 * @param array $remainder The unused params
		 * @return string The decompiled route
		 */
		static private function decompile($route, $params, &$remainder = array())
		{
			$remainder = $params;

			if (preg_match_all(self::REGEX_TOKEN, $route, $matches)) {
				foreach ($matches[0] as $token) {
					$split_pos = strrpos($token, ':');

					if ($split_pos !== FALSE) {
						$param     = trim(substr($token, $split_pos + 1, -1));
						$transform = trim(substr($token, 1, $split_pos - 1));
					} else {
						$param     = trim($token, '[ ]');
						$transform = NULL;
					}

					if (!isset($params[$param])) {
						throw new Flourish\ProgrammerException(
							'Missing parameter %s in supplied parameters',
							$param
						);
					}

					$value = $params[$param];

					switch ($transform) {
						case NULL:
							break;
						case 'uc':
							$value = Flourish\Text::create($value)->camelize(TRUE);
							break;
						case 'lc':
							$value = Flourish\Text::create($value)->camelize();
							break;
						case 'us':
							$value = Flourish\Text::create($value)->underscorize();
							break;
						default:
							throw new Flourish\ProgrammerException(
								'Invalid decompilation transformation type %s',
								$transform
							);
					}

					$route = str_replace($token, $value, $route);
					unset($remainder[$param]);
				}
			}

			return $route;
		}


		/**
		 * Triggers a general ContinueException and logs the message passed
		 *
		 * @static
		 * @access private
		 * @param string $message An sprintf style message
		 * @param mixed $component A component of the message
		 * @param ...
		 * @return void
		 */
		static private function triggerContinue($message, $component)
		{
			$components  = func_get_args();
			$message     = array_shift($components);
			$this->log[] = vsprintf('Continue: ' . $message, $components);

			throw new Flourish\ContinueException(
				'Error running route, continuing...'
			);
		}


		/**
		 * Construct a routes collection
		 *
		 * @access public
		 * @return void
		 */
		public function __construct()
		{

		}


		/**
		 * Composes a route from various components, taking redirects into account
		 *
		 * @access public
		 * @param string $route The route to compose
		 * @param array $components A list of components mapping param name => value
		 * @return string The URL with route tokens replaced by respective components
		 */
		public function compose($route, $components)
		{
			$url = self::decompile($route, $components);

			while ($this->translate($url) !== FALSE);
			return $url;
		}


		/**
		 * Links a route to an action in the routes collection
		 *
		 * If an existing link matches matches the same compiled pattern then the action is
		 * checked for compatibility.  In short, closures are never compatible, strings callables
		 * that match are OK, and array callables that match are OK.
		 *
		 * @access public
		 * @param string $route The route key/mapping
		 * @param callable $action The action to execute, callback strings are custom
		 * @return void
		 * @throws Flourish\ProgrammerException in the case of conflicting routes
		 */
		public function link($route, $action)
		{
			list($pattern, $params) = self::compile($route);

			if (isset($this->links[$pattern])) {

				$existing_action = $this->links[$pattern]['action'];

				if (is_string($existing_action)) {
					if (!is_string($action) || $existing_action != $action) {
						throw new Flourish\ProgrammerException(
							'Cannot add conflicting route %s, conflicting action %s',
							$route,
							$action
						);
					}
				} elseif (is_array($existing_action)) {
					if (!is_array($action) || (object) $existing_action != (object) $action) {
						throw new Flourish\ProgrammerException(
							'Cannot add conflicting route %s, incompatible object callback',
							$route
						);
					}
				} elseif (is_closure($existing_action)) {
					throw new Flourish\ProgrammerException(
						'Cannot add conflicting route %s, action is a closure',
						$route
					);
				}
			}

			$this->links[$pattern] = [
				'action' => $action,
				'params' => $params,
				'route'  => $route
			];
		}


		/**
		 * Redirects a route to a translation in the routes collection
		 *
		 * @access public
		 * @param string $route The route key/mapping
		 * @param string $translation The translation to map to
		 * @param integer $type The type of redirect (301, 303, 307, etc...)
		 * @return void
		 * @throws Flourish\ProgrammerException in the case of conflicting routes
		 */
		public function redirect($route, $translation, $type = 301)
		{
			list($pattern, $params) = self::compile($route);

			if (isset($this->redirects[$pattern])) {

				$existing_translation = $this->redirects[$pattern]['translation'];

				if ($type != $existing_type) {
					throw new Flourish\ProgrammerException(
						'Cannot add conflicting redirect %s, incompatible type %s',
						$route,
						$type
					);
				} elseif ($translation != $existing_translation) {
					throw new Flourish\ProgrammerException(
						'Cannot add conflicting redirect %s, incompatible translation %s',
						$route,
						$translation
					);
				}
			}

			$this->redirects[$pattern] = [
				'route'       => $route,
				'translation' => $translation,
				'params'      => $params,
				'type'        => $type
			];
		}


		/**
		 * "Runs" the routes collection relative to a provided request
		 *
		 * This iterates over all available links in the collection and attempts to match the path
		 * of the request object against each link's compild pattern.  If a match is found it will
		 * attempt to dispatch to the linked action.
		 *
		 * @access public
		 * @param Request $request The request to run against
		 * @return mixed The response of the dispatched action
		 */
		public function run(Interfaces\Request $request)
		{
			$this->errors  = array();
			$request_uri   = $request->getPath();
			$redirect_type = $this->translate($request_uri);

			if ($redirect_type !== FALSE) {
				$request->redirect($request_uri, $redirect_type);
			}

			foreach ($this->links as $pattern => $link) {
				try {

					$this->controller = NULL;
					$old_get          = $_GET;

					if (preg_match('#^' . $pattern . '$#', $request_uri, $matches)) {
						array_shift($matches);

						$route  = $link['route'];
						$params = array_combine($link['params'], $matches);
						$action = is_string($link['action'])
							? self::decompile($link['action'], $params, $params)
							: $link['action'];

						$_GET     = array_merge($_GET, $params);
						$response = $this->dispatch($request, $route, $action);
					}

				} catch (Flourish\ContinueException $e) {
					$_GET = $old_get;
					continue;

				} catch (Flourish\YieldException $e) {
					$_GET = $old_get;

					if (isset($this->controller)) {
						$response = $this->controller->getError();
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
		 * @param string The original route
		 * @param callable $action The action we're dispatching to
		 * @return mixed The response of the action
		 */
		private function dispatch($request, $route, $action)
		{
			if ($action instanceof \Closure) {

				//
				// Call Closures directly
				//

				return $action(['request' => $request, 'routes'  => $this]);

			} elseif (is_string($action)) {

				//
				// Strings are either direct function calls or parseable object callbacks
				//

				if (strpos('::', $action) === FALSE) {
					if (!is_callable($action)) {
						self::triggerContinue(
							'Action "%s" is not callable: Skipping',
							$action
						);
					}

					ob_start();
					$response = call_user_func($action);
					$output   = ob_get_clean();

					return $output ? $output : $response;
				}

				list($action_class, $action_method) = self::parseAction($action);

				if (!class_exists($action_class)) {
					self::triggerContinue(
						'Action class "%s" does not exist: Skipping',
						$action_class
					);
				}

				if (!in_array(self::CONTROLLER_INTERFACE, class_implements($action_class))) {
					self::triggerContinue(
						'Action class "%s" does not implement %s',
						$action_class,
						self::CONTROLLER_INTERFACE
					);
				}

				if (strpos('__', $action_method) === 0) {
					self::triggerContinue(
						'Action method "%s" cannot be a magic method',
						$action_method
					);
				}

				if (!method_exists($action_class, $action_method)) {
					self::triggerContinue(
						'Action method "%s" does not exist for class %s',
						$action_method,
						$action_class
					);
				}

				$this->controller = new $action_class(['request' => $request, 'routes'  => $this]);

				if (!is_callable([$controller, $action_method])) {
					self::triggerContinue(
						'Action method "%s" is not callable on object of class %s',
						$action_method,
						$action_class
					);
				}

				return $this->controller->$action_method();

			} else {

				//
				// Invalid action type
				//

				self::triggerContinue(
					'Invalid action "%s" for route %s',
					$action,
					$route
				);
			}
		}

		/**
		 * Translates a url from the available redirects
		 *
		 * @access private
		 * @param sring $url The URL to translate
		 * @return integer|boolean The type of redirect that should occur, FALSE if none
		 */
		private function translate(&$url)
		{
			foreach ($this->redirects as $pattern => $redirect) {
				if (preg_match('#^' . $pattern . '$#', $url, $matches)) {
					array_shift($matches);

					$params = array_combine($redirect['params'], $matches);
					$url    = self::decompile($redirect['translation'], $params);

					return $redirect['type'];
				}
			}

			return FALSE;
		}
	}
}
