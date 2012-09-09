<?php namespace Dotink\Inkwell
{
	/**
	 * Request class responsible for creating / executing requests.
	 *
	 * @copyright Copyright (c) 2012, Matthew J. Sahagian, others
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 * @author Will Bond           [wb]  <will@flourishlib.com>
	 *
	 * @license Please reference the LICENSE.txt file at the root of this distribution
	 *
	 * @package Dotink\Inkwell
	 */

	use Dotink\Flourish;
	use Dotink\Interfaces;

	class Request implements Interfaces\Inkwell, Interfaces\Request
	{
		/**
		 * The accept header of this request
		 *
		 * @access private
		 * @var string
		 */
		private $accept = NULL;


		/**
		 * The accept languages header of this request
		 *
		 * @access private
		 * @var string
		 */
		private $acceptLanguages = NULL;


		/**
		 * Backup data when filter() is used
		 *
		 * @access private
		 * @var array
		 */
		private $backupData = array();


		/**
		 * Backup files when filter() is used
		 *
		 * @access private
		 * @var string
		 */
		private $backupFiles = array();


		/**
		 * The current request data (original or filtered)
		 *
		 * @access private
		 * @var string
		 */
		private $data = array();


		/**
		 * The current request files (original or filtered)
		 *
		 * @access private
		 * @var string
		 */
		private $files = array();


		/**
		 * The  protocol for the request
		 *
		 * @access private
		 * @var string
		 */
		private $protocol = NULL;


		/**
		 * The request URL
		 *
		 * @access private
		 * @var string
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
			return TRUE;
		}


		/**
		 * Recursively handles casting values
		 *
		 * @param string|array $value The value to be casted
		 * @param string $cast_to The data type to cast to
		 * @param integer $level The nesting level of the call
		 * @return mixed The casted `$value`
		 */
		static private function cast($value, $cast_to, $level=0)
		{
			$level++;

			$strict_array = substr($cast_to, -2) == '[]';
			$array_type   = $cast_to == 'array' || $strict_array;

			if ($level == 1 && $array_type) {
				if (is_string($value) && strpos($value, ',') !== FALSE) {
					$value = explode(',', $value);
				} elseif ($value === NULL || $value === '') {
					$value = array();
				} else {
					settype($value, 'array');
				}
			}

			//
			// Iterate through array values and cast them individually
			//

			if (is_array($value)) {
				if ($cast_to == 'array' || $cast_to === NULL || ($strict_array && $level == 1)) {
					if ($value === array()) {
						return $value;
					}
					foreach ($value as $key => $sub_value) {
						$value[$key] = self::cast($sub_value, $cast_to, $level);
					}
					return $value;
				}
			}

			if ($array_type) {
				$cast_to = preg_replace('#\[\]$#D', '', $cast_to);
			}

			if ($cast_to == 'array' && $level > 1) {
				$cast_to = 'string';
			}

			if (get_magic_quotes_gpc() && (self::isPost() || self::isGet())) {
				$value = self::stripSlashes($value);
			}

			// This normalizes an empty element to NULL
			if ($cast_to === NULL && $value === '') {
				$value = NULL;

			} elseif ($cast_to == 'date') {
				try {
					$value = new Flourish\Date($value);
				} catch (Flourish\ValidationException $e) {
					$value = new Flourish\Date();
				}

			} elseif ($cast_to == 'time') {
				try {
					$value = new Flourish\Time($value);
				} catch (Flourish\ValidationException $e) {
					$value = new Flourish\Time();
				}

			} elseif ($cast_to == 'timestamp') {
				try {
					$value = new Flourish\Timestamp($value);
				} catch (Flourish\ValidationException $e) {
					$value = new Flourish\Timestamp();
				}

			} elseif ($cast_to == 'bool' || $cast_to == 'boolean') {
				if (strtolower($value) == 'false' || strtolower($value) == 'no' || !$value) {
					$value = FALSE;
				} else {
					$value = TRUE;
				}

			} elseif (($cast_to == 'int' || $cast_to == 'integer') && is_string($value) && preg_match('#^-?\d+$#D', $value)) {
				// Only explicitly cast integers than can be represented by a real
				// PHP integer to prevent truncation due to 32 bit integer limits
				if (strval(intval($value)) == $value) {
					$value = (int) $value;
				}

			// This patches PHP bug #53632 for vulnerable versions of PHP - http://bugs.php.net/bug.php?id=53632
			} elseif ($cast_to == 'float' && $value === "2.2250738585072011e-308") {
				static $vulnerable_to_53632 = NULL;

				if ($vulnerable_to_53632 === NULL) {
					$running_version = preg_replace(
						'#^(\d+\.\d+\.\d+).*$#D',
						'\1',
						PHP_VERSION
					);
					$vulnerable_to_53632 = version_compare($running_version, '5.2.17', '<') || (version_compare($running_version, '5.3.5', '<') && version_compare($running_version, '5.3.0', '>='));
				}

				if ($vulnerable_to_53632) {
					$value = "2.2250738585072012e-308";
				}

				settype($value, 'float');

			} elseif ($cast_to != 'binary' && $cast_to !== NULL) {
				$cast_to = str_replace('integer!', 'integer', $cast_to);
				settype($value, $cast_to);
			}

			// Clean values coming in to ensure we don't have invalid UTF-8
			if (($cast_to === NULL || $cast_to == 'string' || $cast_to == 'array') && $value !== NULL) {
				$value = self::stripLowOrderBytes($value);
				$value = Flourish\UTF8::clean($value);
			}

			return $value;
		}


 		/**
		 * Returns the best HTTP `Accept-*` header item match, optionally filtered
		 *
		 * @param string $header_value The value of the Accept* header to process
		 * @param array $options  A list of supported options to pick the best from
		 * @return string The best accept item, FALSE if none are valid, NULL if all are accepted
		 */
		static private function pickBestAcceptItem($header_value, $options)
		{
			settype($options, 'array');

			if (!strlen($header_value)) {
				if (empty($options)) {

					//
					// Return null immediately if header value is empty and no options are set
					//

					return NULL;
				}

				//
				// Return the first option immediately if our header value is empty.
				//

				return reset($options);
			}

			$items = self::processAcceptHeader($header_value);
			reset($items);

			if (!$options) {
				$result = key($items);
				if ($result == '*/*' || $result == '*') {
					$result = NULL;
				}
				return $result;
			}

			$top_q    = 0;
			$top_item = FALSE;

			foreach ($options as $option) {
				foreach ($items as $item => $q) {
					if ($q < $top_q) {
						continue;
					}

					// Type matches have /s
					if (strpos($item, '/') !== FALSE) {
						$regex = '#^' . str_replace('*', '.*', $item) . '$#iD';

					// Language matches that don't have a - are a wildcard
					} elseif (strpos($item, '-') === FALSE) {
						$regex = '#^' . str_replace('*', '.*', $item) . '(-.*)?$#iD';

					// Non-wildcard languages are straight-up matches
					} else {
						$regex = '#^' . str_replace('*', '.*', $item) . '$#iD';
					}

					if (preg_match($regex, $option) && $top_q < $q) {
						$top_q = $q;
						$top_item = $option;
						continue;
					}
				}
			}

			return $top_item;
		}


		/**
		 * Returns an array of values from one of the HTTP `Accept-*` headers
		 *
		 * The format of the returned array is `{value} => {quality}` sorted (in a stable-fashion)
		 * from highest to lowest `q` - an empty array is returned if the header is empty.
		 *
		 * @access private
		 * @param string $header_value The value of the Accept-* header to process
		 * @return array An associative array of values, sorted by quality
		 */
		static private function processAcceptHeader($header_value)
		{
			if (!strlen($_SERVER[$header_value])) {
				return array();
			}

			$types  = explode(',', $header_value);
			$output = array();

			//
			// We use this suffix to force stable sorting with the built-in sort function
			//

			$suffix = sizeof($types);

			foreach ($types as $type) {
				$parts = explode(';', $type);

				if (!empty($parts[1]) && preg_match('#^q=(\d(?:\.\d)?)#', $parts[1], $match)) {
					$q = number_format((float)$match[1], 5);
				} else {
					$q = number_format(1.0, 5);
				}

				$q .= $suffix--;

				$output[trim($parts[0])] = $q;
			}

			arsort($output, SORT_NUMERIC);

			foreach ($output as $type => $q) {
				$output[$type] = (float) substr($q, 0, -1);
			}

			return $output;
		}


		/**
		 * Removes low-order bytes from a value
		 *
		 * @acces private
		 * @param string|array $value The value to strip
		 * @return string|array The `$value` with low-order bytes stripped
		 */
		static private function stripLowOrderBytes($value)
		{
			if (is_array($value)) {
				foreach ($value as $key => $sub_value) {
					$value[$key] = self::stripLowOrderBytes($sub_value);
				}
				return $value;
			}
			return preg_replace('#[\x00-\x08\x0B\x0C\x0E-\x1F]#', '', $value);
		}


		/**
		 * Removes slashes from a value
		 *
		 * @access private
		 * @param string|array $value The value to strip
		 * @return string|array The `$value` with slashes stripped
		 */
		static private function stripSlashes($value)
		{
			if (is_array($value)) {
				foreach ($value as $key => $sub_value) {
					$value[$key] = self::stripSlashes($sub_value);
				}
				return $value;
			}
			return stripslashes($value);
		}


		/**
		 * Construct a new request.  Empty arguments are pulled from the current request.
		 *
		 * @access public
		 * @param string $method The method, e.g. get, put, post, etc...
		 * @param string $accept A valid HTTP Accept string for requesting content type
		 * @param string|Flourish\URL $url The URL for the request
		 * @param string|array $data A query string or array to pull data from
		 * @return void
		 */
		public function __construct($method = NULL, $accept = NULL, $url = NULL, $data = NULL)
		{
			$valid_methods  = [GET, POST, PUT, DELETE, HEAD];
			$this->method   = strtolower(!$method ? $_SERVER['REQUEST_METHOD'] : $method);
			$this->accept   = $accept;
			$this->url      = new Flourish\URL($url);
			$this->data     = array();
			$this->files    = $_FILES;
			$this->protocol = isset($_SERVER['SERVER_PROTOCOL'])
				? $_SERVER['SERVER_PROTOCOL']
				: 'HTTP/1.1';

			if (!in_array($this->method, $valid_methods)) {
				throw new Flourish\ValidationException(
					'Invalid method "%s" specified, must be one of %s',
					$this->method,
					join(', ', $valid_methods)
				);
			}

			if (!$this->accept && isset($_SERVER['HTTP_ACCEPT'])) {
				$this->accept = $_SERVER['HTTP_ACCEPT'];
			}

			if (isset($_SERVER['HTTP_ACCEPT_LANGUAGES'])) {
				$this->acceptLanguages = $_SERVER['HTTP_ACCEPT_LANGUAGES'];
			}

			if ($data == NULL) {
				if ($this->checkMethod(GET)) {
					$this->data == $_GET;
				} else{
					$this->data = array_replace_recursive($_GET, $_POST);

					if ($this->checkMethod(PUT) || $this->checkMethod(DELETE)) {
						parse_str(file_get_contents('php://input'), $input_data);

						$this->data = array_replace_recursive($this->data, $input_data);
					}
				}

			} else {
				if (is_string($data)) {
					parse_str($data, $this->data);
				} elseif (is_array($data)) {
					$this->data = $data;
				} else {
					throw new Flourish\ProgrammerException(
						'Invalid data passed, must be valid query string or array'
					);
				}
			}
		}


		/**
		 * Indicated if the parameter specified is set in the request data
		 *
		 * @access public
		 * @param string $key The key to check - array elements can be checked via `[sub-key]`
		 * @return boolean TRUE if the parameter is set, FALSE otherwise
		 */
		public function check($key)
		{
			$dereference = NULL;

			if (strpos($key, '[')) {
				$bracket_pos = strpos($key, '[');
				$dereference = substr($key, $bracket_pos);
				$key         = substr($key, 0, $bracket_pos);
			}

			if (!isset($this->data[$key])) {

				//
				// Return immediately if key is not found
				//

				return FALSE;
			}

			if ($dereference) {
				preg_match_all('#(?<=\[)[^\[\]]+(?=\])#', $dereference, $keys, PREG_SET_ORDER);

				$array_keys = array_map('current', $keys);
				$value      =& $this->data;

				array_unshift($keys, $key);

				foreach (array_slice($keys, 0, -1) as $array_key) {
					if (!is_array($value) || !isset($value[$array_key])) {
						$value = NULL;
					} else {
						$value = $value[$array_key];
					}
				}

				$key = end($array_keys);
			}

			return isset($this->data[$key]);
		}

		/**
		 * Checks whether or not the request's method matches a given value
		 *
		 * @access public
		 * @param $string $method The method to check for a match
		 * @return boolean TRUE if the method matches, FALSE otherwise
		 */
		public function checkMethod($method)
		{
			return strtolower($method) == $this->method;
		}

		/**
		 * Gets a value from ::get() and passes it through Flourish\HTML::encode()
		 *
		 * @access public
		 * @param string $key The key to get - array elements can be accessed via `[sub-key]`
		 * @param string $cast_to Cast the value to this data type
		 * @param mixed $default_value The value to be used if the parameter is no set
		 * @return string The encoded value
		 */
		public function encode($key, $cast_to = NULL, $default_value = NULL)
		{
			return Flourish\HTML::encode($this->get($key, $cast_to, $default_value));
		}


		/**
		 * Parses through request data and filters out everything not matching the prefix.
		 *
		 * @access public
		 * @param string $prefix The prefix to filter by
		 * @param mixed $key If the field is an array, get the value corresponding to this key
		 * @return void
		 */
		public function filter($prefix, $key = NULL)
		{
			$regex               = '#^' . preg_quote($prefix, '#') . '#';
			$current_backup      = sizeof($this->backupFiles);

			//
			// Treat our files somewhat differently than the rest of our data
			//

			$this->backupFiles[] = $this->files;
			$this->files         = array();

			foreach ($this->backupFiles[$current_backup] as $field => $value) {
				$matches_prefix = !$prefix || ($prefix && strpos($field, $prefix) === 0);

				if ($matches_prefix && is_array($value) && isset($value['name'][$key])) {
					$new_field = preg_replace($regex, '', $field);

					$this->files[$new_field]             = array();
					$this->files[$new_field]['name']     = $value['name'][$key];
					$this->files[$new_field]['type']     = $value['type'][$key];
					$this->files[$new_field]['tmp_name'] = $value['tmp_name'][$key];
					$this->files[$new_field]['error']    = $value['error'][$key];
					$this->files[$new_field]['size']     = $value['size'][$key];
				}
			}

			//
			// Do the rest of our data
			//

			$this->backupData[] = $this->data;
			$this->data         = array();

			foreach ($this->backupData[$current_backup] as $field => $value) {
				$matches_prefix = !$prefix || ($prefix && strpos($field, $prefix) === 0);

				if ($matches_prefix) {
					$new_field = preg_replace($regex, '', $field);

					if (is_array($value) && $key !== NULL && isset($value[$key])) {
						$this->data[$new_field] = $value[$key];
					} else {
						$this->data[$new_field] = $value;
					}
				}
			}

		}


		/**
		 * Gets a value from the request data
		 *
		 * A value that exactly equals `''` and is not cast to a specific type will
		 * become `NULL`.
		 *
		 * Valid `$cast_to` types include:
		 *  - `'string'`
		 *  - `'binary'`
		 *  - `'int'`
		 *  - `'integer'`
		 *  - `'bool'`
		 *  - `'boolean'`
		 *  - `'array'`
		 *  - `'date'`
		 *  - `'time'`
		 *  - `'timestamp'`
		 *
		 * It is possible to append a `?` to a data type to return `NULL`
		 * whenever the `$key` was not specified in the request, or if the value
		 * was a blank string.
		 *
		 * The `array` and unspecified `$cast_to` types allow for multi-dimensional
		 * arrays of string data. It is possible to cast an input value as a
		 * single-dimensional array of a specific type by appending `[]` to the
		 * `$cast_to`.
		 *
		 * All `string`, `array` or unspecified `$cast_to` will result in the value(s)
		 * being interpreted as UTF-8 string and appropriately cleaned of invalid
		 * byte sequences. Also, all low-byte, non-printable characters will be
		 * stripped from the value. This includes all bytes less than the value of
		 * 32 (Space) other than Tab (`\t`), Newline (`\n`) and Cariage Return
		 * (`\r`).
		 *
		 * To preserve low-byte, non-printable characters, or get the raw value
		 * without cleaning invalid UTF-8 byte sequences, plase use the value of
		 * `binary` for the `$cast_to` parameter.
		 *
		 * Any integers that are beyond the range of 32bit storage will be returned
		 * as a string. The returned value can be forced to always be a real
		 * integer, which may cause truncation of the value, by passing `integer!`
		 * as the `$cast_to`.
		 *
		 * @param string $key The key to get - array elements can be accessed via `[sub-key]`
		 * @param string $cast_to Cast the value to this data type
		 * @param mixed $default_value If the parameter is not set use this value instead.
		 * @param boolean $default_on_blank Return NULL if request value and default are empty
		 * @return mixed The value
		 */
		public function get($key, $cast_to = NULL, $default = NULL, $default_on_blank = FALSE)
		{

			$value       = $default;
			$dereference = NULL;

			if (strpos($key, '[')) {
				$bracket_pos       = strpos($key, '[');
				$array_dereference = substr($key, $bracket_pos);
				$key               = substr($key, 0, $bracket_pos);
			}

			if (isset($this->data[$key])) {
				$value = $this->data[$key];
			}

			if ($value === '' && $default_on_blank && $default !== NULL) {
				$value = $default;
			}

			if ($dereference) {
				preg_match_all('#(?<=\[)[^\[\]]+(?=\])#', $dereference, $keys, PREG_SET_ORDER);

				$keys = array_map('current', $keys);

				foreach ($keys as $key) {
					if (!is_array($value) || !isset($value[$key])) {
						$value = $default;
						break;
					}

					$value = $value[$key];
				}
			}

			//
			// This allows for data_type? casts to allow NULL through
			//

			if ($cast_to !== NULL && substr($cast_to, -1) == '?') {
				if ($value === NULL || $value === '') {
					return NULL;
				}
				$cast_to = substr($cast_to, 0, -1);
			}

			return self::cast($value, $cast_to);
		}


		/**
		 * Returns the HTTP `Accept-Language`s sorted by their `q` values (from high to low)
		 *
		 * The returned array format is `{language} => {q value}` sorted (in a stable-fashion)
		 * from highest to lowest `q` - if no header was sent, an empty array will be returned.
		 *
		 * @access public
		 * @return array An associative array of accept languages, sorted by equality
		 */
		public function getAcceptLanguages()
		{
			return self::processAcceptHeader($this->acceptLanguages);
		}


		/**
		 * Returns the HTTP `Accept` types sorted by their `q` values (from high to low)
		 *
		 * The returned array format is `{type} => {q value}` sorted (in a stable-fashion) from
		 * highest to lowest `q` - if no header was sent, an empty array will be returned.
		 *
		 * @access public
		 * @return array An associative array of accept types, sorted by quality
		 */
		public function getAcceptTypes()
		{
			return self::processAcceptHeader($this->accept);
		}


		/**
		 * Returns the best HTTP `Accept-Language`, optionally filtered
		 *
		 * Special conditions affecting the return value:
		 *
		 *  - If no `$filter` is provided and the client does not send the `Accept-Language`
		 *    header, `NULL` will be returned
		 *  - If no `$filter` is provided and the client specifies `*` with the highest `q`, `NULL`
		 *    will be returned
		 *  - If `$filter` contains one or more values, but the `Accept-Language` header does not
		 *    match any, `FALSE` will be returned
		 *  - If `$filter` contains one or more values, but the client does not send the
		 *    `Accept-Language` header, the first entry from `$filter` will be returned
		 *  - If `$filter` contains two or more values, and two of the values have the same `q`
		 *    value, the one listed first in `$filter` will be returned
		 *
		 * @access public
		 * @param array|string $filter Acceptable language(s) - e.g. `en-us`
		 * @param string ...
		 * @return string|NULL|FALSE The best language listed in the `Accept-Language` header
		 */
		public function getBestAcceptLanguage($filter=array())
		{
			if (!is_array($filter)) {
				$filter = func_get_args();
			}

			return self::pickBestAcceptItem($this->acceptLanguages, $filter);
		}


		/**
		 * Returns the best HTTP `Accept`, optionally filtered
		 *
		 * Special conditions affecting the return value:
		 *
		 *  - If no `$filter` is provided and the client does not send the `Accept` header,
		 *    `NULL` will be returned
		 *  - If no `$filter` is provided and the client specifies `{@*}*` with the highest `q`,
		 *    `NULL` will be returned
		 *  - If `$filter` contains one or more values, but the `Accept` header does not match any,
		 *    `FALSE` will be returned
		 *  - If `$filter` contains one or more values, but the client does not send the `Accept`
		 *    header, the first entry from `$filter` will be returned
		 *  - If `$filter` contains two or more values, and two of the values have the same `q`
		 *    value, the one listed first in `$filter` will be returned
		 *
		 * @access public
		 * @param array|string $filter Acceptable type(s)
		 * @param string ...
		 * @return string|NULL|FALSE The best type listed in the `Accept` header
		 */
		public function getBestAcceptType($filter=array())
		{
			if (!is_array($filter)) {
				$filter = func_get_args();
			}

			return self::pickBestAcceptItem($this->accept, $filter);
		}


		/**
		 * Gets a value from the request data, restricting to a specific set of values
		 *
		 * @access public
		 * @param string $key The key to get - array elements can be accessed via `[sub-key]`
		 * @param array $valid_values The values that are permissible, the first will act as default
		 * @return mixed The value
		 */
		public function getValid($key, $valid_values)
		{
			settype($valid_values, 'array');

			$valid_values = array_merge(array_unique($valid_values));
			$value        = $this->get($key);

			if (!in_array($value, $valid_values)) {
				return $valid_values[0];
			}

			return $value;
		}


		/**
		 * Gets the path of the request URL
		 *
		 * @access public
		 * @return string The path of the request URL
		 */
		public function getPath()
		{
			return $this->url->getPath();
		}


		/**
		 * Gets a value from ::get() and passes it through Flourish\HTML::prepare()
		 *
		 * @access public
		 * @param string $key The key to get - array elements can be accessed via `[sub-key]`
		 * @param string $cast_to Cast the value to this data type
		 * @param mixed $default_value The value to be used if the parameter is no set
		 * @return string  The prepared value
		 */
		public function prepare($key, $cast_to=NULL, $default_value=NULL)
		{
			return Flourish\HTML::prepare($this->get($key, $cast_to, $default_value));
		}


		/**
		 * Redirects the request.
		 *
		 * The $url parameter can be anything compatible with the modify() method of Flourish\URL.
		 * This includes but is not limited to, a partial URL as a string, an array of url
		 * component replacements, or a URL object iself.
		 *
		 * It is also important to note that this method always exits.
		 *
		 * @access public
		 * @param Flourish\URL|array|string $url The URL to redirect to
		 * @param integer $type The type of redirect, defaults to 303
		 * @return void
		 */
		public function redirect($url = NULL, $type = 303)
		{
			$this->url = $this->url->modify($url);

			if ($this->protocol == 'HTTP/1.0') {
				switch ($type) {
					case 301:
						header('HTTP/1.0 301 Moved Permanently');
						break;
					case 302:
					case 303:
					case 307:
						header('HTTP/1.0 302 Moved Temporarily');
						break;
				}
			} elseif ($this->protocol == 'HTTP/1.1') {
				switch ($type) {
					case 301:
						header('HTTP/1.1 301 Moved Permanently');
						break;
					case 302:
						header('HTTP/1.1 302 Found');
						break;
					case 303:
						header('HTTP/1.1 303 See Other');
						break;
					case 307:
						header('HTTP/1.1 307 Temporary Redirect');
						break;
				}
			}

			header('Location: ' . $this->url);
			exit(0);
		}

		/**
		 * Sets a value into the request data
		 *
		 * @access public
		 * @param string $key  The key to set - array elements can be modified via `[sub-key]`
		 * @param mixed $value The value to set
		 * @return void
		 */
		public function set($key, $value)
		{
			$tip =& $this->data;

			if ($bracket_pos = strpos($key, '[')) {
				$dereference = substr($key, $bracket_pos);
				$key         = substr($key, 0, $bracket_pos);

				preg_match_all('#(?<=\[)[^\[\]]+(?=\])#', $dereference, $keys, PREG_SET_ORDER);

				$keys = array_map('current', $keys);
				array_unshift($keys, $key);

				foreach (array_slice($keys, 0, -1) as $array_key) {
					if (!isset($tip[$array_key]) || !is_array($tip[$array_key])) {
						$tip[$array_key] = array();
					}
					$tip =& $tip[$array_key];
				}

				$tip[end($array_keys)] = $value;

			} else {
				$tip[$key] = $value;
			}
		}


		/**
		 * Returns request data to the state it was in before ::filter() was called
		 *
		 * @access public
		 * @return void
		 */
		public function unfilter()
		{
			if (count($this->backupFiles)) {
				$this->files = array_pop($this->fileBackups);
			}

			if (count($this->backupData)) {
				$this->data  = array_pop($this->dataBackups);
			}
		}
	}
}
