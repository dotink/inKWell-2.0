<?php

	return self::create('Library', array(

		// Whether or not we should attempt to auto scaffold records using this class.

		'auto_scaffold' => TRUE,

		// The directory relative to application root in which user defined record sets are
		// stored.

		'root_directory' => 'user/models/sets',

		// The wildcard autoloader means to use this classes __match() method and attemp to load
		// from it's root directory.  Removing it will remove record set autoloading.

		'autoloaders' => array('*')

	));
