<?php

	include(__DIR__ . '/library/EnhanceTestFramework.php');
	include(__DIR__ . '/../../includes/core.php');

	\Enhance\Core::discoverTests(__DIR__ . '/tests');
	\Enhance\Core::runTests();