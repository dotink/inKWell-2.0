<?php namespace Dotink\Lab {

	class Assert
	{
		const REGEXP_PHP_VARIABLE = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';

		private $value = NULL;
		private $type  = NULL;
		private $isMethod = FALSE;
		private $isFunction = FALSE;
		private $isString = FALSE;

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

		public function equals($value)
		{
			if ($this->isMethod || $this->isFunction) {
				$call   = $this->call;
				$result = $call($this->args);

				if ($result != $value) {
					throw new \Exception(sprintf(
						'Assertion failed, expected %s but got %s',
						print_r($value,  TRUE),
						print_r($result, TRUE)
					));
				}
			}
		}

		public function with()
		{
			if ($this->isMethod || $this->isFunction || $this->isClosure) {
				$this->args = func_get_args();
			} else {
				throw new \Exception(sprintf(
					'Cannot assert with on non-callable value %s',
					print_r($this->value, TRUE)
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


		private function reflect($value)
		{
			list($class, $method) = explode('::', $value);
			$reflection           = new \ReflectionMethod($class, $method);
			$this->isMethod       = TRUE;

			if ($reflection->isStatic()) {
				$this->call = function($args) use ($reflection) {
					if ($reflection->isPrivate() || $reflection->isProtected()) {
						$reflection->setAccessible(TRUE);
					}

					return $reflection->invokeArgs(NULL, $args);
				};
			}
		}
	}

}
