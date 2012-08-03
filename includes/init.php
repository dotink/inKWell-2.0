<?php

	include 'core.php';
	include 'functions.php';

	//
	// BEGIN INITIALIZATION LOGIC
	//

	$config = isset($_SERVER['IW_CONFIG']) ? $_SERVER['IW_CONFIG'] : NULL;

	//
	// END INITIALIZATION LOGIC
	//

	return \Dotink\Inkwell\IW::init(realpath(dirname(__DIR__)), $config);
