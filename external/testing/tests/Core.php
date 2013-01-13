<?php namespace Dotink\Lab {

	use Dotink\Parody;
	use Dotink\Inkwell;

	return [

		'setup' => function($config) {
			needs($config['app_root'] . DS . 'includes/core.php');

			Parody\Mime::define('App\Text');

			Parody\Mime::define('Dotink\Flourish\ProgrammerException')
				-> extending('Dotink\Flourish\UnexpectedException')
				-> extending('Dotink\Flourish\Exception');

		},

		'cleanup' => function($config) {

		},

		'tests' => [

			/**
			 * Tests method with a fake class with both preceding namespace separator and without.
			 */
			'transformClassToIW()' => function($config) {

				Parody\Mime::create('App\Text')
					-> onCall('create')
					-> expect('Vendor' . DS . 'Project')
					-> give(function($mime) {
						return $mime
							-> onCall('underscorize') -> give('vendor' . DS . 'project')
							-> resolve();
					});

				assert('Dotink\Inkwell\IW::transformClassToIW')
					-> with('\Vendor\Project\Class')
					-> equals('vendor/project/Class.php');

				assert('Dotink\Inkwell\IW::transformClassToIW')
					-> with('Vendor\Project\Class')
					-> equals('vendor/project/Class.php');

			},

			/**
			 * Tests method with a fake class with both preceding namespace separator and without.
			 * As well as a class with an underscore.
			 */
			'transformClassToPSR0()' => function($config) {

				assert('Dotink\Inkwell\IW::transformClassToPSR0')
					-> with('\Vendor\Project\Class')
					-> equals('Vendor/Project/Class.php');

				assert('Dotink\Inkwell\IW::transformClassToPSR0')
					-> with('Vendor\Project\Class')
					-> equals('Vendor/Project/Class.php');

				assert('Dotink\Inkwell\IW::transformClassToPSR0')
					-> with('Vendor\Project\Example_Class')
					-> equals('Vendor/Project/Example/Class.php');
			},

			/**
			 * Tests method with by providing a valid callable from the class itself and also by
			 * providing an invalid callable (with a bad namespace) and making sure that an
			 * exception is thrown.
			 */
			'addLoadingStandard()' => function($config) {

				$app = new Inkwell\IW($config['app_root']);

				assert('Dotink\Inkwell\IW::addLoadingStandard')
					-> using($app)
					-> with('IW', 'Dotink\Inkwell\IW::transformClassToIW')
					-> equals('Dotink\Inkwell\IW::transformClassToIW');

				assert('Dotink\Inkwell\IW::addLoadingStandard')
					-> using($app)
					-> with('IW', 'IW::transformClassToIW')
					-> throws('Dotink\Flourish\ProgrammerException');
			}
		]
	];
}
