<?php

namespace Dotink\Flourish {

	/**
	 * Override class_exists for some classes to always return `FALSE`
	 *
	 * @param string $class The class to load
	 * @return boolean Always returns FALSE
	 */
	function class_exists($class) {

		$override_classes = [
			'Dotink\Flourish\Text' // prevent internal calls to text from being triggered
		];

		if (in_array($class, $override_classes)) {
			return FALSE;
		}

		return \class_exists($class);
	}
}
