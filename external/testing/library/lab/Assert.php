<?php namespace Dotink\Lab {

	class Assert
	{
		const REGEXP_PHP_VARIABLE = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';

		private $value = NULL;
		private $type  = NULL;
		private $isMethod = FALSE;
		private $isFunction = FALSE;
		private $isClosure = FALSE;
		private $isString = FALSE;
		private $needsObject = TRUE;
		private $object = NULL;
		private $args = array();

		/**
		 *
		 */
		public function __construct($value)
		{
			$this->value = $value;
			$this->type  = gettype($value);

			switch ($this->type) {
				case 'string':
					$this->loadString($value);
					break;

				default:
					throw new \Exception(sprintf(
						'Cannot assert type %s, not supported',
						$this->type
					));

			}
		}


		/**
		 *
		 */
		public function checkObject()
		{
			if ($this->isMethod && $this->needsObject) {
				if ($this->object === NULL) {
					throw new \Exception(sprintf(
						'Cannot test assertion without an object, see: using()'
					));
				} else {
					return TRUE;
				}
			}

			return FALSE;
		}


		public function resolveValue()
		{
			if ($this->isMethod || $this->isFunction || $this->isClosure) {
				$call   = $this->call;
				$result = $this->checkObject()
					? $call($this->object, $this->args)
					: $call($this->args);

			} else {
				return $this->value;
			}

			return $result;
		}


		public function throws($class)
		{
			if (!($this->isMethod || $this->isFunction || $this->isClosure)) {
				throw new \Exception(sprintf(
					'Cannot assert that non-callable value %s throws an exception',
					$this->printVar($this->value)
				));
			}

			try {
				$result = $this->resolveValue();

			} catch (\Exception $e) {
				$exception_class = get_class($e);

				if ($exception_class == $class) {
					return;
				} else {
					throw new \Exception(sprintf(
						'Assertion failed, callable %s threw exception of type %s instead of %s',
						$this->value,
						$exception_class,
						$class
					));
				}
			}

			throw new \Exception(sprintf(
				'Assertion failed, callable %s returned %s instead of throwing exception %s',
				$this->value,
				$this->printVar($result),
				$class
			));
		}

		/**
		 *
		 */
		public function equals($value)
		{
			$result = $this->resolveValue();

			if ($result != $value) {
				throw new \Exception(sprintf(
					'Assertion failed, expected %s but got %s',
					$this->printVar($value,  TRUE),
					$this->printVar($result, TRUE)
				));
			}
		}


		/**
		 *
		 */
		public function using($object) {
			$class = get_class($object);

			if ($class != $this->class) {
				throw new \Exception(sprintf(
					'Cannot use %s for assertion on %s, invalid object class %s',
					$class,
					$this->value,
					$this->class
				));
			}

			$this->object = $object;

			return $this;
		}


		/**
		 *
		 */
		public function with()
		{
			if ($this->isMethod || $this->isFunction || $this->isClosure) {
				$this->args = func_get_args();
			} else {
				throw new \Exception(sprintf(
					'Non-callable value %s cannot used arguments provied by with()',
					$this->printVar($this->value, TRUE)
				));
			}

			return $this;
		}


		/**
		 *
		 */
		private function loadString($value)
		{
			if (strpos($this->value, '::') !== FALSE) {
				$is_call = preg_match(
					'#' . self::REGEXP_PHP_VARIABLE . '\:\:' . self::REGEXP_PHP_VARIABLE . '#',
					$this->value
				);

				if ($is_call) {
					$this->reflect($value);
				}

			} elseif (function_exists($value)) {
				$this->isFunction = TRUE;

			} else {
				$this->isString = TRUE;
			}
		}


		/**
		 *
		 */
		private function printVar($var)
		{
			switch($type = gettype($var)) {
				case 'object':
					$value = get_class($var);
					break;
				case 'string':
					$value = '"' . $var . '"';
					break;
				case 'array':
					$value = count($var);
					break;
				case 'boolean':
					$value = $var
						? 'TRUE'
						: 'FALSE';
					break;
				case 'NULL':
					$type  = NULL;
					$value = 'NULL';
					break;
				default:
					$value = $var;
					break;
			}

			return ($type ? '[' . $type . ']' : '') . '(' . $value . ')';
		}

		private function reflect($value)
		{
			list($class, $method) = explode('::', $value);
			$reflection           = new \ReflectionMethod($class, $method);
			$this->isMethod       = TRUE;
			$this->class          = ltrim($class, '\\');
			$this->method         = $method;

			if ($reflection->isStatic()) {
				$this->needsObject = FALSE;
			}

			if (!$this->needsObject) {
				$this->call = function($args) use ($reflection) {
					if (!$reflection->isPublic()) {
						$reflection->setAccessible(TRUE);
					}

					return $reflection->invokeArgs(NULL, $args);
				};

			} else {
				$this->call = function($object, $args) use ($reflection) {
					if (!$reflection->isPublic()) {
						$reflection->setAccessible(TRUE);
					}

					return $reflection->invokeArgs($object, $args);
				};
			}
		}
	}

}
