<?php

	return self::create('Core', array(

		//
		// A non-wilcard string which does not represent a class means that an
		// attempt will be made to load any class from these directories.
		//

		'library' => 'IW:   library',
		'vendor'  => 'PSR0: vendor'
	));