<?php

	return self::create('Core', array(

		'Dotink\Flourish\*'  => 'includes/lib/flourish',
		'Dotink\Inkwell\*'   => 'includes/lib',

		// A non-wilcard string which does not represent a class means that an
		// attempt will be made to load any class from these directories.

		'library'      => 'IW:   includes/lib',
		'vendor'       => 'PSR0: includes/vendor'
	));