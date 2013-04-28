<?php namespace Dotink\Inkwell
{
	use App;
	use Dotink\Flourish;
	use Dotink\Interfaces;

	/**
	 * Response Class
	 *
	 * @copyright Copyright (c) 2012, Matthew J. Sahagian
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 *
	 * @license Please reference the LICENSE.txt file at the root of this distribution
	 *
	 * @package Dotink\Inkwell
	 */
	class Response implements Interfaces\Inkwell, Interfaces\Response
	{
		const DEFAULT_CACHE_DIRECTORY = 'cache/responses';
		const DEFAULT_RESPONSE        = HTTP\NOT_FOUND;


		/**
		 * Location of the cache directory
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $cacheDirectory = NULL;


		/**
		 * A list of available renderers
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $renderers = array();


		/**
		 * A list of available render methods
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $renderMethods = array();


		/**
		 * A list of available responses
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $states = array();


		/**
		 * A list of mime types which indicate text formats
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $textTypes = [
			'text/html',
			'application/json',
			'application/xhtml+xml',
			'application/xml',
			'text/plain',
		];


		/**
		 * The view for the response
		 *
		 * @access protected
		 * @var mixed
		 */
		protected $view = NULL;


		/**
		 * The status of the response, ex: 'ok'
		 *
		 * @access private
		 * @var string
		 */
		private $status = NULL;


		/**
		 * The code of the response, ex: 200
		 *
		 * @access private
		 * @var integer
		 */
		private $code = NULL;


		/**
		 * The content/mime type of the response, ex: 'text/html'
		 *
		 * @access private
		 * @var string
		 */
		private $type = NULL;


		/**
		 * A list of headers to output if the response is sent
		 *
		 * @access private
		 * @var array
		 */
		private $headers = array();


		/**
		 * The render hooks which will be applied to the view on sending
		 *
		 * @access private
		 * @var array
		 */
		private $renderHooks = array();


		/**
		 * Initialize the class
		 *
		 * @param Dotink\Inkwell\IW $app The application instance loading the class
		 * @param array $config The configuration array for the class
		 * @return boolean TRUE on success, FALSE on failure
		 */
		static public function __init($app, $config = array())
		{
			self::$cacheDirectory = isset($config['cache_directory'])
				? $app->getWriteDirectory($config['cache_directory'])
				: $app->getWriteDirectory(self::DEFAULT_CACHE_DIRECTORY);

			if (isset($config['states'])) {
				self::$states = array_merge(self::$states, $config['states']);
			}

			foreach ($app['config']->getByType('array', '@rendering', 'methods') as $methods) {
				foreach ($methods as $class => $function) {
					self::registerRenderMethod($class, $function);
				}
			}

			return TRUE;
		}


		/**
		 * Registers a render method for a particular class
		 *
		 * This allows for classes to modularly set a render method to be used during response
		 * resolution of a view.  In short, if the view ends up as an instance of the provided
		 * class, it will call the given method to render it.  This is an exact match so
		 * a class which might inherit from a class with an existing registered method needs to
		 * register its own again.
		 *
		 * @static
		 * @access public
		 * @param string $class The class to register a render method for.
		 * @param string $method The method to call on the object to render it
		 * @return void
		 */
		static public function registerRenderMethod($class, $method)
		{
			self::$renderMethods[strtolower($class)] = $method;
		}


		/**
		 * Rendering callback for object typed views.
		 *
		 * @static
		 * @access protected
		 * @param Response $response The response to render
		 * @return void
		 */
		static protected function renderObject($response)
		{
			if (is_object($response->view)) {
				$view_class = get_class($response->view);
				$render_key = strtolower($view_class);

				if (isset(self::$renderMethods[$render_key])) {
					$method         = self::$renderMethods[$render_key];
					$response->view = $response->view->$method();
				} elseif (is_callable(array($response->view, '__toString'))) {
					$response->view = (string) $response->view;
				} else {
					$response->view = $view_class;
				}
			}
		}


		/**
		 * This will send a cache file for the current unique URL based on a mime type.
		 *
		 * The response will only be sent if the cached response is less than the $max_age
		 * parameter in seconds.  This defaults to 120, meaning that if the file is older
		 * than 2 minutes, this function will return.  Otherwise, the cache file is sent and
		 * the script exits.
		 *
		 * @static
		 * @access public
		 * @param string $type The mime type for the cached response
		 * @param string $max_age The time in seconds when the cache shouldn't be used, default 120
		 * @param string $entropy_data A string to calculate entropy from, default NULL
		 * @param string $max_entropy The maximum amount of entropy allowed, default 0
		 */
		static public function sendCache($type, $max_age = 120, $entropy = NULL, $max_entropy = 0)
		{
			return;
		}


		/**
		 * Resolves a response state name into the appropriate code
		 *
		 * @static
		 * @access protected
		 * @param string $state The , ex: 'ok' or 'not_found'
		 * @return int The response code
		 * @throws Flourish\ProgrammerException if the response code is undefined or non-numeric
		 */
		static protected function translateCode($state)
		{
			if (isset(self::$states[$state]['code'])) {
				$response_code = self::$states[$state]['code'];

				if (is_numeric($response_code)) {
					return $response_code;
				}
			}

			throw new Flourish\ProgrammerException(
				'Cannot create response with undefined or invalid state "%s"',
				$state
			);
		}


		/**
		 * Caches a file for the current unique URL using the data type as part of its id.
		 *
		 * @static
		 * @access private
		 * @param string $data_type The data type for the request to match the cache
		 * @param string $data The data to cache
		 */
		static private function cache($data_type, $data)
		{
			if (!$data_type) {
				$data_type = 'text/plain';
			}

			$url        = new Flourish\URL;
			$cache_id   = md5($url->getPathWithQuery() . $data_type);
			$cache_file = new Flourish\File(self::$cacheDirectory . DS . $cache_id . '.txt');

			if (!$cache_file->exists() || $cache_file->read() != $data) {
				$cache_file->write($data);
			}

			return $cache_file;
		}

		/**
		 * Constructs a new response
		 *
		 * @access public
		 * @return void
		 */
		public function __construct($status = NULL, $type = NULL, $headers = array(), $view = NULL)
		{
			$this($status, $type, $headers, $view);
		}

		/**
		 * Creates or recreates the object with information other than the defaults
		 *
		 * This method has multiple signatures depending on the parameter count:
		 *
		 * $response($status)
		 * $response($status, $view)
		 * $response($status, $type, $view)
		 * $response($status, $type, $headers, $view)
		 *
		 * @access public
		 * @param string $status
		 * @param string $type
		 * @param array $headers
		 * @param string $view
		 */
		public function __invoke($status, $type = NULL, $headers = array(), $view = NULL)
		{
			$this->status = !$status
				? self::DEFAULT_RESPONSE
				: $status;

			$this->code = self::translateCode($this->status);

			switch (func_num_args()) {
				case 2:
					$this->type    = NULL;
					$this->view    = func_get_arg(1);
					break;

				case 3:
					$this->type    = strtolower($type);
					$this->view    = func_get_arg(2);
					break;

				default:
					$this->type    = strtolower($type);
					$this->headers = $headers;
					$this->view    = $view;
			}

			$this->renderHooks = array();

			foreach (self::$renderers as $type_match => $callback) {
				if (preg_match('#' . $type_match . '#', $this->type)) {
						$this->renderHooks[] = $callback;
				}
			}

			return $this;
		}


		/**
		 * Gets the status set on the response
		 *
		 *
		 */
		public function checkCode($min = NULL, $max = NULL)
		{
			if (!$max) {
				return $this->code == $min;
			} else {
				return $this->code >= $min && $this->code <= $max;
			}
		}


		/**
		 * Gets the status set on the response
		 *
		 * @access public
		 * @return boolean
		 */
		public function checkStatus($status)
		{
			return $this->status == $status;
		}


		/**
		 * Gets the status set on the response
		 *
		 * @access public
		 * @return int The current HTTP code for the response
		 */
		public function getCode()
		{
			return $this->code;
		}


		/**
		 * Gets the status set on the response
		 *
		 * @access public
		 * @return string The current HTTP status for the response
		 */
		public function getStatus()
		{
			return $this->status;
		}


		/**
		 * Resolves a response one way or another.
		 *
		 * This will basically turn whatever you pass it into a response object.  The assumption
		 * is, if you actually pass it data, that you are looking to return "ok".  It will use the
		 * cache system to make attempts to determine the mime type and cache it for future use.
		 *
		 * @access public
		 * @param mixed $response The response to resolve
		 * @return Response
		 */
		static public function resolve($content = NULL)
		{
			if (!($content instanceof self)) {
				$response = new self();

				if ($content === NULL) {
					$response(HTTP\NOT_FOUND);

				} elseif (empty($content)) {
					$response(HTTP\NO_CONTENT);

				} else {
					$response(HTTP\OK, NULL, array(), $content);
				}

			} else {
				$response = $content;
			}

			return $response;
		}


		/**
		 * Set an individual header on the response
		 *
		 * @access public
		 * @param string $header The header to set
		 * @param string $value The value for it
		 * @return Response The response object for chaining
		 */
		public function setHeader($header, $value)
		{
			$this->headers[$header] = $value;
			return $this;
		}


		/**
		 * Sends the response to the screen
		 *
		 * @access public
		 * @param boolean $headers_only Whether or not we're only sending headers
		 * @return integer The status of the request
		 */
		public function send($headers_only = FALSE)
		{
			$version  = end(explode($_SERVER['SERVER_PROTOCOL'], '/'));
			$aliases  = array(
				'1.0' => array( 405 => 400, 406 => 400 /* NO NEED FOR REDIRECTS */ ),
				'1.1' => array( /* CURRENT VERSION OF HTTP SO WE SHOULD BE GOOD */ )
			);

			//
			// If for some reason we have been provided a NULL view we should try to
			// see if we have a default body for the response type.  If we don't, let's
			// provide a legitimate no content response.
			//

			if ($this->view === NULL) {
				if (isset(self::$states[$this->status]['body'])) {
					$this->view = self::$states[$this->status]['body'];
					$this->view = Flourish\Text::create($this->view)->compose();
				} else {
					$this->code   = 204;
					$this->status = HTTP\NO_CONTENT;
				}
			}

			//
			// We want to let any renderers work their magic before doing anything else.  A good
			// renderer will do whatever it can to resolve the response to a string.  Otherwise
			// whatever the response is will be casted as a (string) and may not do what one
			// expects.
			//

			if ($this->view !== NULL) {

				if (is_object($this->view)) {
					$view_class = get_class($this->view);
					$class_key  = strtolower($view_class);

					if (isset(self::$renderMethods[$class_key])) {
						$method = self::$renderMethods[$class_key];

						if (!is_callable([$this->view, $method])) {
							throw new Flourish\ProgrammerException(
								'Cannot render view with registered non-callable method %s()',
								$method
							);
						}

					} elseif (is_callable([$this->view, '__toString'])) {
						$method = '__toString';

					} else {
						throw new Flourish\ProgrammerException(
							'Cannot render object of class %s, no rendering method available',
							$view_class
						);
					}

					$this->view = $this->view->$method();
				}
			}

			$this->view = (string) $this->view;
			$this->code = isset($aliases[$version][$this->code])
				? $aliases[$version][$this->code]
				: $this->code;

			//
			// If we don't have a type set we will try to determine the type by caching
			// our view as a file and getting its mimeType.
			//

			$this->type = (!$this->type)
				? self::cache(NULL, $this->view)->getMimeType()
				: $this->type;

			//
			// Output all of our headers.
			//
			// Apparently fastCGI explicitly does not like the standard header format, so
			// so we send different leading headers based on that.  The content type downward,
			// however, is exactly the same.
			//

			$headers = [
				!Flourish\Core::checkSAPI('cgi-fcgi')
					? sprintf('%s %d %s', $_SERVER['SERVER_PROTOCOL'], $this->code, $this->status)
					: sprintf('Status: %d %s', $this->code, $this->status)
			];

			if ($this->code != 204) {
				if (in_array($this->type, self::$textTypes)) {
					$headers[] = sprintf('Content-Type: %s; charset=utf-8', $this->type);
				} else {
					$headers[] = sprintf('Content-Type: %s', $this->type);
				}
			}

			foreach ($this->headers as $header => $value) {
				if ($value !== NULL) {
					$headers[] = $header . ': ' . $value;
				}
			}

			if ($headers_only && Flourish\Core::checkSAPI('cli')) {
				foreach ($headers as $header) {
					print($header . LB);
				}
			} else {
				foreach ($headers as $header) {
					header($header);
				}
			}

			if (!$headers_only) {
				print($this->view);
			}

			return $this->code;
		}
	}
}
