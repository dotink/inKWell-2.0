<?php namespace Dotink\Lab {

	include(__DIR__ . '/library/parody/Jest.php');
	include(__DIR__ . '/library/parody/Mime.php');
	include(__DIR__ . '/library/lab/Assert.php');
	include(__DIR__ . '/library/Colors.php');

	use Dotink\Parody;

	const DS                  = \DIRECTORY_SEPARATOR;
	const LB                  = \PHP_EOL;
	const TAB                 = "\t";

	const REGEX_ABSOLUTE_PATH = '#^(/|\\\\|[a-z]:(\\\\|/)|\\\\|//)#i';

	/**
	 * Get a message printed in a particular color
	 *
	 * @param string $message The Message to print
	 * @param string $color The color to print it in
	 * @return string The colored message for CLI output
	 */
	function _($message, $color) {
		static $ansi = NULL;

		$ansi = new \Colors();

		return $ansi->getColoredString($message, $color);
	}


	/**
	 *
	 */
	function assert($value)
	{
		return new Assert($value);
	}


	/**
	 * Gets the configuration for the system
	 *
	 * @return array The system configuration
	 */
	function get_config()
	{
		try {
			return needs(__DIR__ . DS . 'lab.config');
		} catch (\Exception $e) {
			echo _('Config Failed: ', 'red');
			echo $e->getMessage() . LB;
			echo LB;
			exit(-1);
		}
	}


	/**
	 * Depend on a file being included
	 *
	 * @param string $file The file to depend on
	 * @return mixed The return result from including the file
	 */
	function needs($file)
	{
		if (!is_readable($file)) {
			throw new \Exception(sprintf(
				'Cannot include %s, file is not readable',
				$file
			));
		}

		exec(PHP_BINARY . ' -l ' . escapeshellarg($file), $output);

		$output = implode("\n", $output);

		if (strpos($output, 'Parse error') !== FALSE) {
			throw new \Exception(sprintf(
				'Cannot include %s, syntax error',
				$output
			));
		}

		return include $file;
	}


	/**
	 * Adds tests from a directory
	 *
	 * @param string $directory
	 */
	function add_tests($directory)
	{
		$fixtures = array();
		$fixtures = array_merge($fixtures, glob($directory . DS . '*.php'));

		foreach (glob($directory . DS . '*', GLOB_ONLYDIR) as $sub_directory) {
			$fixtures = array_merge($fixtures, add_tests($sub_directory));
		}

		return $fixtures;
	}

	/**
	 * Execute Fixtures and Tests.  If no argument was passed to lab, then we will scan the
	 * configured testDirectory.  Otherwise will will execute the single fixture available in the
	 * file path passed as the first argument.
	 *
	 * @return void
	 */
	call_user_func(function() use($argv) {
		if (!isset($argv[1])) {

			$banner = (
				' _        _    ____          _   ___  ' . LB .
				'| |      / \  | __ )  __   _/ | / _ \ ' . LB .
				'| |     / _ \ |  _ \  \ \ / / || | | |' . LB .
				'| |___ / ___ \| |_) |  \ V /| || |_| |' . LB .
				'|_____/_/   \_\____/    \_/ |_(_)___/  By: Dotink'
			);

			echo _($banner, 'dark_gray') . LB . LB;

			$config         = get_config();
			$test_directory = !preg_match(REGEX_ABSOLUTE_PATH, $config['tests_directory'])
				? realpath(__DIR__ . DS . $config['tests_directory'])
				: $config['tests_directory'];

			$fixtures = add_tests($test_directory);

			foreach ($fixtures as $fixture) {
				passthru(
					PHP_BINARY . ' ' . __FILE__ . ' ' . escapeshellarg($fixture),
					$status
				);

				if ($status !== 0) {
					exit(-1);
				}
			}

			return;
		}

		$config   = get_config();
		$fixture  = require($argv[1]);
		$headline = sprintf('Running %s', pathinfo($argv[1], PATHINFO_FILENAME));

		echo _($headline, 'blue') . LB;

		try {
			if (isset($config['setup']) && $config['setup'] instanceof \Closure) {
				call_user_func($config['setup'], $config);
			}

			if (isset($fixture['setup']) && $fixture['setup'] instanceof \Closure) {
				call_user_func($fixture['setup'], $config);
			}
		} catch (\Exception $e) {
			echo _('Setup Failed: ', 'red');
			echo $e->getMessage() . LB;
			echo LB;
			exit(-1);
		}

		if (isset($fixture['tests']) && is_array($fixture['tests'])) {
			foreach ($fixture['tests'] as $message => $test) {
				if ($test instanceof \Closure) {
					echo TAB . ' - ' . $message . ' ';

					try {
						call_user_func($test, $config);
						echo '[' . _('PASS', 'green') . ']' . LB;

					} catch (\Exception $e) {
						echo '[' . _('FAIL', 'red')   . ']' . LB;
						echo LB;
						echo $e->getMessage() . LB;
						echo LB;
						exit(-1);
					}
				}
			}
		}

		try {
			if (isset($fixture['cleanup']) && $fixture['cleanup'] instanceof \Closure) {
				call_user_func($fixture['cleanup'], $config);
			}

			if (isset($config['cleanup']) && $config['cleanup'] instanceof \Closure) {
				call_user_func($config['cleanup'], $config);
			}
		} catch (\Exception $e) {
			echo _('Cleanup Failed: ', 'red');
			echo $e->getMessage() . LB;
			echo LB;
			exit(-1);
		}
	});
}
