<?php namespace Dotink\Inkwell {

	try {

		call_user_func(function() {

			//
			// Track backwards until we discover our includes directory.  The only file required
			// to be in place for this is includes/init.php which should return our application
			// instance.
			//

			for (

				//
				// Initial assignment
				//

				$include_directory = 'includes';

				//
				// While Condition
				//

				!is_dir($include_directory);

				//
				// Modifier
				//

				$include_directory = realpath('..' . DIRECTORY_SEPARATOR . $include_directory)
			);

			//
			// Boostrap!
			//

			$init    = $include_directory . DIRECTORY_SEPARATOR . 'init.php';
			$routing = $include_directory . DIRECTORY_SEPARATOR . 'routing.php';

			if (!is_readable($init) {
				throw new \Exception('Unable to include inititialization file.');
			}

			if (!is_readable($routing)) {
				throw new \Exception('Unable to include routing file.');
			}

			$app      = include($init);
			$response = include($routing);
			$status   = Response::resolve($response)->send();

			exit($status);
		});

	} catch (Exception $e) {

		//
		// Panic here, attempt to determine what state we're in, see if some
		// errors handlers are callable or if we're totally fucked.  In the
		// end, throw the exception and let Flourish handle it appropriately.
		//

		throw $e;

		exit(0);
	}
}
