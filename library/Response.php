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
		const DEFAULT_TYPE            = 'text/html';

		/**
		 * Location of the cache directory
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $cacheDirectory = NULL;


		/**
		 * Default type
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $defaultType = NULL;


		/**
		 * A list of available filters
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $renderFilters = array();


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
		 * The code of the response, ex: 200
		 *
		 * @access private
		 * @var integer
		 */
		private $code = NULL;


		/**
		 * A list of headers to output if the response is sent
		 *
		 * @access private
		 * @var array
		 */
		private $headers = array();


		/**
		 * The language for this response
		 *
		 * @access private
		 * @var string
		 */
		private $lang = NULL;


		/**
		 * The method for this response
		 *
		 * @access private
		 * @var string
		 */
		private $method = NULL;


		/**
		 * The render hooks which will be applied to the view on sending
		 *
		 * @access private
		 * @var array
		 */
		private $renderHooks = array();


		/**
		 * The request for which this response is created
		 *
		 * @access private
		 * @var array
		 */
		private $request = NULL;


		/**
		 * The status of the response, ex: 'ok'
		 *
		 * @access private
		 * @var string
		 */
		private $status = NULL;


		/**
		 * The content/mime type of the response, ex: 'text/html'
		 *
		 * @access private
		 * @var string
		 */
		private $type = NULL;


		/**
		 * The URL which requested this response
		 *
		 * @access private
		 * @var Object A url object
		 */
		private $url = NULL;


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

			self::$defaultType = isset($config['default_type'])
				? $config['default_type']
				: self::DEFAULT_TYPE;

			if (isset($config['states'])) {
				self::$states = array_merge(self::$states, $config['states']);
			}

			foreach ($app['config']->getByType('array', '@rendering') as $rendering_config) {
				if (isset($rendering_config['filters'])) {
					foreach ($rendering_config['filters'] as $mime_type => $filters) {
						self::registerRenderFilter($mime_type, $filters);
					}
				}

				if (isset($rendering_config['methods'])) {
					foreach ($rendering_config['methods'] as $class => $function) {
						self::registerRenderMethod($class, $function);
					}
				}
			}

			return TRUE;
		}


		/**
		 * Registers a rendering filter
		 *
		 * @static
		 * @access public
		 * @param string $class The class to register a render method for.
		 * @param string $method The method to call on the object to render it
		 * @return void
		 */
		static public function registerRenderFilter($mime_type, $filter)
		{
			$mime_type = strtolower($mime_type);

			if (!is_array($filter)) {
				$filter = [(string) $filter];
			}

			if (!isset(self::$filters[$mime_type])) {
				self::$renderFilters[$mime_type] = array();
			}

			self::$renderFilters[$mime_type] = array_merge(
				self::$renderFilters[$mime_type],
				$filter
			);
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
				$statef
			);
		}


		/**
		 * Generates or validates an etag in apc cache
		 *
		 * @static
		 * @access private
		 * @param string $cache_id The cache id for the resource
		 * @param string $etag The etag for the resource
		 * @param boolean $validate Whether or not we should just validate
		 * @return boolean TRUE if validating and the etag matches, FALSE otherwise
		 */
		static private function etag($cache_id, $etag, $validate = FALSE)
		{
			if (extension_loaded('apc') && ini_get('apc.enabled')) {
				$key = __CLASS__ . '::ETAGS::' . $cache_id;

				if ($validate) {
					return (apc_fetch($key) == $etag);
				} else {
					apc_store($key, $etag);
				}
			}

			return FALSE;
		}


		/**
		 * Create a new response, invoking the default status
		 *
		 * @access public
		 * @return void
		 */
		public function __construct()
		{
			$this(NULL);
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

			return $this;
		}


		/**
		 * Checks the code on the response
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
		 * Checks the status on a response
		 *
		 * @access public
		 * @return boolean
		 */
		public function checkStatus($status)
		{
			return $this->status == $status;
		}


		/**
		 * Gets the code set on the response
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
		public function resolve($content = NULL)
		{
			if (!($content instanceof self)) {
				$response = clone $this;

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
		 * Sends the response to the screen
		 *
		 * @access public
		 * @param boolean $headers_only Whether or not we're only sending headers
		 * @return integer The status of the request
		 */
		public function send($headers_only = FALSE)
		{
			$protocol = explode($_SERVER['SERVER_PROTOCOL'], '/');
			$version  = end($protocol);
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
					$this->view = App\Text::create($this->view)->compose();

				} else {
					$this->code   = 204;
					$this->status = HTTP\NO_CONTENT;
				}

			}

			//
			// We want to let any renderers work their magic before doing anything else.  A
			// good renderer will do whatever it can to resolve the response to a string.
			// Otherwise whatever the response is will be casted as a (string) and may not do
			// what one expects.
			//
			// NOTE: This logic is kept separate from the if statement above, in the event
			// the default body for a response needs additional processing.
			//

			if ($this->view !== NULL) {
				if (isset(self::$renderFilters[$this->type])) {
					foreach (self::$renderFilters[$this->type] as $filter) {
						if ($filter::filter($this)) {
							break;
						}
					}
				}

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
			// Now that our view is rendered to a string and we have our code looked up
			// we want to deal with caching and etag generation.
			//

			$etag          = $this->request->getHeader('If-None-Match');
			$cache_control = $this->request->getHeader('Cache-Control');
			$cache_file    = $this->cache($etag);

			if (!$cache_file) {
				$this->view   = NULL;
				$this->status = 'Not Modified';
				$this->code   = 304;

			} else {
				$this->setHeader('Etag', $etag);

				if (strpos($cache_control, 'no-store') !== FALSE) {
					$cache_file->delete();
				}
			}

			//
			// Output all of our headers.
			//
			// Apparently fastCGI explicitly does not like the standard header format, so
			// so we send different leading headers based on that.  The content type downward,
			// however, is exactly the same.
			//

			$headers = [
				!App\Core::checkSAPI('cgi-fcgi')
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

			if ($headers_only && App\Core::checkSAPI('cli')) {
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
		 * Sets the request for which the response is intended
		 *
		 * @access public
		 * @param Interfaces\Request $request The request object
		 * @return void
		 */
		public function setRequest(Interfaces\Request $request)
		{
			$this->request = $request;
			$this->url     = $request->getURL();
			$this->lang    = $request->getBestAcceptLanguage();
			$this->method  = $request->getMethod();
		}


		/**
		 * Caches a file for the current unique URL using the data type as part of its id.
		 *
		 * @access private
		 * @param string $etag
		 * @return App\File
		 */
		private function cache(&$etag)
		{
			$cache_id  = $this->generateCacheId();
			$data_hash = md5($this->view);

			if (self::etag($cache_id, $etag, TRUE) && $etag == $data_hash) {
				return NULL;
			}

			self::etag($cache_id, $etag = $data_hash);

			if (!$this->type) {
				$extension = pathinfo($this->url->getPath(), PATHINFO_EXTENSION);
				$temp_file = $cache_id . ($extension ? '.' . $extension : NULL);
				$temp_file = new App\File(self::$cacheDirectory . DS . $temp_file);

				$temp_file->write($this->view);

				$this->type = $temp_file->getMimeType();

				if (!$extension && $this->type == 'text/plain') {
					$this->type = self::$defaultType;
				}

				$cache_id   = $this->generateCacheId();
				$cache_file = $temp_file->rename($cache_id, TRUE);

				self::etag($cache_id, $data_hash);

			} else {
				$cache_file = new App\File(self::$cacheDirectory . DS . $cache_id);
			}

			return $cache_file;
		}


		/**
		 * Generates a cache ID for the response
		 *
		 * @access private
		 * @return string A hash representing the cache id for the response
		 */
		private function generateCacheId()
		{
			return md5($this->url . $this->method . $this->type . $this->lang);
		}
	}
}
