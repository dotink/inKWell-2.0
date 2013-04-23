<?php namespace Dotink\Inkwell
{
	use App;
	use Dotink\Flourish;
	use Dotink\Interfaces;
	use Dotink\Traits;

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
	class Router implements Interfaces\Inkwell, Interfaces\Router
	{
		use Traits\Emitter;

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
			'+' => '([1-9]|[1-9][0-9]+)',
			'#' => '([-]?(?:[0-9]+))',
			'%' => '([-]?[0-9]+\.[0-9]+',
			'!' => '([^/]+)',
			'$' => '([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)',
			'*' => '(.+)'
		];


		/**
		 * Whether or not we should allow for restless urls, i.e. ending / is the same as without
		 *
		 * @static
		 * @access private
		 * @var boolean
		 */
		static private $restless = FALSE;


		/**
		 * The current action controller
		 *
		 * @access private
		 * @var Interfaces\Controller
		 */
		private $controller = NULL;


		/**
		 * The entry controller
		 *
		 * @access private
		 * @var string
		 */
		private $entry = NULL;


		/**
		 * The entry Action
		 *
		 * @access private
		 * @var string
		 */
		private $entryAction = NULL;


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
		 * Initialize the class
		 *
		 * @param Dotink\Inkwell\IW $app The application instance loading the class
		 * @param array $config The configuration array for the class
		 * @return boolean TRUE on success, FALSE on failure
		 */
		static public function __init($app, Array $config = array())
		{
			if (isset($config['restless']) && $config['restless']) {
				self::$restless = TRUE;
			}
		}


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
		static private function decompile($route, $params, &$remainder = NULL)
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
							$value = str_replace('-', '_', $value);
							$value = App\Text::create($value)->camelize(TRUE);
							break;
						case 'lc':
							$value = str_replace('-', '_', $value);
							$value = App\Text::create($value)->camelize();
							break;
						case 'us':
							$value = App\Text::create($value)->underscorize();
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
		 * Construct a router
		 *
		 * @access public
		 * @return void
		 */
		public function __construct()
		{

		}


		/**
		 * Check whether or not a given class and method are the entry controller and action
		 *
		 * If a only a single argument is given it will be checked against the class only
		 *
		 * @access public
		 * @param string $class The class to check
		 * @param string $method The method to check
		 * @return boolean TRUE if the parameters match this instance's entry and action
		 */
		public function checkEntryAction($class, $method = NULL)
		{
			if (func_num_args == 1) {
				return $this->entry == $class;
			}

			return $this->entry == $class && $this->action == $method;
		}


		/**
		 * Composes a route from various components, taking redirects into account
		 *
		 * @access public
		 * @param string $route The route to compose
		 * @param array $components A list of components mapping param name => value
		 * @param boolean $remainder_as_query Whether or not to include extra components as a query
		 * @return string The URL with route tokens replaced by respective components
		 */
		public function compose($route, $components, $remainder_as_query = TRUE)
		{
			$url = self::decompile($route, $components, $remainder);

			while ($this->translateRedirect($url) !== NULL);

			if ($remainder_as_query && count($remainder)) {
				$url = $url . '?' . http_build_query($remainder, '', '&',  PHP_QUERY_RFC3986);
			}

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
				$existing_type        = $this->redirects[$pattern]['type'];

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
		 * @param Interfaces\Request $request The request to run against
		 * @param Interfaces\Response $response The response to use
		 * @return mixed The response of the dispatched action
		 */
		public function run(Interfaces\Request $request, Interfaces\Response $response)
		{
			$restless_uri  = NULL;
			$request_uri   = $request->getPath();
			$unused_params = array();

			if (self::$restless) {
				$restless_uri = ($request_uri[strlen($request_uri) - 1] == '/')
					? substr($request_uri, 0, -1)
					: $request_uri . '/';
			}

			if ($redirect_type = $this->translateRedirect($request_uri, $restless_uri)) {
				$request->redirect($request_uri, $redirect_type);
			}

			foreach ($this->links as $pattern => $link) {
				$this->controller = NULL;

				try {
					if (preg_match('#^' . $pattern . '$#', $request_uri, $matches)) {
						array_shift($matches);

						$route  = $link['route'];
						$action = $link['action'];
						$params = array_combine($link['params'], $matches);
						$params = array_map('urldecode', $params);

						if (is_string($action)) {
							$action = self::decompile($action, $params, $unused_params);
							$params = $unused_params;
						}

						foreach ($params as $key => $value) {
							$request->set($key, $value);
						}

						$this->emit('beginAction', $request);

						ob_start();

						if ($action instanceof \Closure) {
							$controller_response = $action([
								'request'  => $request,
								'response' => $response,
								'router'   => $this
							]);

						} elseif (is_array($action)) {
							$this->controller = new $action[0]([
								'request'  => $request,
								'response' => $response,
								'router'   => $this
							]);

							$controller_response = $this->controller->$action[1]();

						} else {
							$controller_response = $action();
						}

						$response = ($output = ob_get_clean())
							? $response(HTTP\OK, NULL, [], $output)
							: $response->resolve($controller_response);

						$this->emit('endAction', $response);

					} elseif (preg_match('#^' . $pattern . '$#', $restless_uri)) {
						$request->redirect($restless_uri, 301);
					}

				} catch (Flourish\ContinueException $e) {
					continue;

				} catch (Flourish\YieldException $e) {
					break;
				}
			}

			return $response;
		}


		/**
		 * Parses an action, triggering errors in the event the action is invalid
		 *
		 * @access private
		 * @param $action The action to parse
		 * @return mixed A suitable action to run
		 */
		private function parseAction($action)
		{
			if ($action instanceof \Closure) {
				return $action;

			} elseif (is_string($action)) {

				//
				// Strings are either direct function calls or parseable object callbacks
				//

				if (strpos($action, '::') === FALSE) {
					if (!is_callable($action)) {
						$this->triggerContinue(
							'Action "%s" is not callable: Skipping',
							$action
						);
					}

					return $action;
				}

				list($class, $method) = explode('::', $action);

				if (!class_exists($class)) {
					$this->triggerContinue(
						'Action class "%s" does not exist: Skipping',
						$class
					);
				}

				if (!in_array(self::CONTROLLER_INTERFACE, class_implements($class))) {
					$this->triggerContinue(
						'Action class "%s" does not implement %s',
						$class,
						self::CONTROLLER_INTERFACE
					);
				}

				if (strpos('__', $method) === 0) {
					$this->triggerContinue(
						'Action method "%s" cannot be a magic method',
						$method
					);
				}

				if (!method_exists($class, $method)) {
					$this->triggerContinue(
						'Action method "%s" does not exist for class %s',
						$method,
						$class
					);
				}

				if (!is_callable([$class, $method])) {
					$this->triggerContinue(
						'Action method "%s" is not callable on object of class %s',
						$method,
						$class
					);
				}

				if (!$this->entry) {
					$this->entryAction = $method;
					$this->entry       = $class;
				}

				return [$class, $method];
			}

			$this->triggerContinue(
				'Invalid action "%s"',
				$action
			);
		}

		/**
		 * Translates a url from the available redirects
		 *
		 * @access private
		 * @param sring $url The URL to translate
		 * @return integer|boolean The type of redirect that should occur, FALSE if none
		 */
		private function translateRedirect(&$request_uri, $restless_uri = NULL)
		{
			$redirect_type = NULL;

			foreach ($this->redirects as $pattern => $redirect) {
				if (preg_match('#^' . $pattern . '$#', $request_uri, $matches)) {
					array_shift($matches);

					$params        = array_combine($redirect['params'], $matches);
					$request_uri   = self::decompile($redirect['translation'], $params);
					$redirect_type = $redirect['type'];

					break;

				} elseif (preg_match('#^' . $pattern . '$#', $restless_uri)) {
					$request_uri   = $restless_uri;
					$redirect_type = 301;

					break;
				}
			}

			return $redirect_type;
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
		private function triggerContinue($message, $component)
		{
			$components  = func_get_args();
			$message     = array_shift($components);
			$this->log[] = vsprintf('Continue: ' . $message, $components);

			throw new Flourish\ContinueException(
				'Error running route, continuing...'
			);
		}
	}
}
