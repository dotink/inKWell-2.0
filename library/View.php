<?php namespace Dotink\Inkwell
{
	use App;
	use ArrayObject;
	use Dotink\Flourish;
	use Dotink\Interfaces;

	/**
	 * View Class
	 *
	 * @copyright Copyright (c) 2012, Matthew J. Sahagian
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 *
	 * @license Please reference the LICENSE.txt file at the root of this distribution
	 *
	 * @package Dotink\Inkwell
	 */
	class View extends ArrayObject implements Interfaces\Inkwell
	{

		const DEFAULT_TEMPLATE = 'main';

		/**
		 * The default view root directory
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $defaultRoot = NULL;


		/**
		 *
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $helperRoot = NULL;


		/**
		 *
		 */
		static private $helpers = array();


		/**
		 *
		 */
		private $head = NULL;


		/**
		 *
		 */
		private $assets = array();

		/**
		 *
		 */
		private $components = array();


		/**
		 *
		 */
		private $currentFile = NULL;


		/**
		 *
		 */
		private $data = array();


		/**
		 *
		 */
		private $root = NULL;


		/**
		 *
		 */
		private $template = NULL;

		/**
		 *
		 */
		private $type = NULL;


		/**
		 * Initialize the class
		 *
		 * @param Dotink\Inkwell\IW $app The application instance loading the class
		 * @param array $config The configuration array for the class
		 * @return boolean TRUE on success, FALSE on failure
		 */
		static public function __init($app, Array $config = array())
		{
			self::$defaultRoot = $app->getRoot(__CLASS__);

			if (isset($config['helper_root_directory'])) {
				self::$helperRoot = $app->getRoot(NULL, $config['helper_root_directory']);
			}
		}


		/**
		 *
		 */
		static public function create($view, array $components = array(), array $data = array())
		{
			if (strpos('.', $view) === FALSE) {
				$view = self::DEFAULT_TEMPLATE . '.' . $view;
			}

			$view = new self($view, self::$defaultRoot);

			return $view($components, $data);
		}


		/**
		 *
		 */
		public function __construct($view, $root)
		{
			if (count($file_parts = explode('.', $view)) != 2) {
				throw new Flourish\ProgrammerException(
					'Invalid view %s specified',
					$view
				);
			}

			$this->template = $file_parts[0];
			$this->type     = $file_parts[1];
			$this->root     = $root;

			if (!isset(self::$helpers[$this->type])) {
				$helper_file = self::$helperRoot . DS . $this->type . '.php';

				if (file_exists($helper_file)) {
					include($helper_file);

					self::$helpers[$this->type] = TRUE;

				} else {
					self::$helpers[$this->type] = FALSE;
				}
			}

			//
			// The head is essentially a non-templated empty copy of this object.  It will
			// be merged with all other heads.
			//

			$this->head = clone $this;
		}


		/**
		 *
		 */
		public function __invoke(array $components, array $data = array())
		{
			foreach ($components as $element => $view) {
				if (isset($this->components[$element])) {
					unset($this->components[$element]);
				}

				$this->add($element, $view);
			}

			$this->exchangeArray($data);

			return $this;
		}


		/**
		 *
		 */
		public function add($element, $view)
		{
			if (isset($this->components[$element])) {
				if (is_array($view)) {
					$this->components[$element] = array_merge(
						$this->components[$element],
						$view
					);

				} else {
					$this->components[$element][] = $view;
				}

			} else {
				if (is_array($view)) {
					$this->components[$element] = $view;
				} else {
					$this->components[$element] = [$view];
				}
			}
		}


		/**
		 *
		 */
		public function asset($element, $asset)
		{
			$this->assets[$element] = $asset;
		}


		/**
		 *
		 */
		public function each($element, $emitter)
		{
			if (!$this->has($element)) {
				return;
			}

			if (is_array($this[$element])) {
				foreach ($this[$element] as $i => $value) {
					$emitter($value, $i);
				}

			} else {
				$emitter($this[$element], 0);
			}
		}


		/**
		 *
		 */
		public function has($element)
		{
			return array_key_exists($element, $this);
		}



		public function join($element, $separator = '::')
		{
			if (!$this->has($element)) {
				return;
			}

			return implode($separator, $this[$element]);
		}


		/**
		 *
		 */
		public function pack($element, $value = NULL)
		{
			if ($value === NULL && is_array($element)) {
				$this->exchangeArray(array_merge($this, $element));
			} else {
				if (is_string($element)) {
					$this[$element] = $value;
				} else {
					throw new Flourish\ProgrammerException(
						'Cannot set element with non-string reference of type %s',
						gettype($element)
					);
				}
			}
		}


		/**
		 *
		 */
		public function peal()
		{
			if (!isset($this[$element])) {
				return NULL;
			}

			return is_array($this[$element])
				? array_pop($this[$element])
				: $this[$element];
		}


		/**
		 *
		 */
		public function pull($element)
		{
			if (!isset($this[$element])) {
				return NULL;
			}

			return is_array($this[$element])
				? array_shift($this[$element])
				: $this[$element];
		}


		/**
		 *
		 */
		public function push($element, $value)
		{
			if (!isset($this[$element])) {
				$this[$element] = [];
			}

			if (!is_array($this[$element])) {
				$this[$element] = [$this[$element]];
			}

			$this[$element][] = $value;
		}


		/**
		 *
		 */
		public function make()
		{
			foreach ($this->components as $element => $list) {
				foreach ($list as $i => $view) {
					if (is_string($view)) {
						$this->currentFile = !preg_match(REGEX\ABSOLUTE_PATH, $view)
							? $this->root . DS . $view . '.php'
							: $view;

						$this->components[$element][$i] = $this->buffer(function() {
							include $this->currentFile;
						});

					} elseif (is_object($view) && $view instanceof self) {
						$view->parent                   = $this;
						$this->components[$element][$i] = $view->make();

					} else {

					}
				}
			}

			$this->currentFile = !preg_match(REGEX\ABSOLUTE_PATH, $this->template)
				? $this->root . DS . $this->template . '.' . $this->type . '.php'
				: $this->template . '.' . $this->type . '.php';

			return $this->buffer(function() {
				include $this->currentFile;
			});
		}

		/**
		 *
		 */
		public function offsetGet($offset)
		{
			//
			// We overload this so we can return null for easy defaults such as the following:
			// $this['element'] ?: 'Default'
			//

			return isset($this[$offset])
				? parent::offsetGet($offset)
				: NULL;
		}


		/**
		 *
		 */
		private function buffer(\Closure $closure)
		{
			if (ob_start()) {
				$closure();
				return ob_get_clean();
			}

			throw new Flourish\EnvironmentException(
				'Failed to start output buffering'
			);
		}


		/**
		 *
		 */
		private function place($element)
		{
			if (isset($this->components[$element])) {
				foreach ($this->components as $list) {
					echo implode($list);
				}
			}
		}
	}
}