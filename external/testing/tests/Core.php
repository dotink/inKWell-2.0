<?php namespace Dotink\Lab {

	use Dotink\Parody\Mime;
	use Dotink\Inkwell;

	return [

		'setup' => function($data) {
			needs($data['root'] . DS . 'includes/core.php');
		},

		'cleanup' => function($config) {

		},

		'tests' => [

			//
			// Tests method with a fake class with both preceding namespace separator and without.
			//

			'transformClassToIW()' => function($config) {
				assert('Dotink\Inkwell\IW::transformClassToIW')
					-> with('\Vendor\Project\Class')
					-> equals('Vendor/Project/Class.php');

				assert('Dotink\Inkwell\IW::transformClassToIW')
					-> with('Vendor\Project\ClassException')
					-> equals('Vendor/Project/Exceptions/ClassException.php');

				assert('Dotink\Inkwell\IW::transformClassToIW')
					-> with('Vendor\Project\ClassTrait')
					-> equals('Vendor/Project/Traits/ClassTrait.php');

				assert('Dotink\Inkwell\IW::transformClassToIW')
					-> with('Vendor\Project\ClassInterface')
					-> equals('Vendor/Project/Interfaces/ClassInterface.php');

			},

			//
			// Tests method with a fake class with both preceding namespace separator and without.
			// As well as a class with an underscore.
			//

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

			//
			// Tests method with by providing a valid callable from the class itself and also by
			// providing an invalid callable (with a bad namespace) and making sure that an
			// exception is thrown.
			//

			'addLoadingStandard()' => function($config) {

				$app = new Inkwell\IW($config['root']);

				assert('Dotink\Inkwell\IW::addLoadingStandard')
					-> using($app)
					-> with('IW', 'Dotink\Inkwell\IW::transformClassToIW')
					-> equals('Dotink\Inkwell\IW::transformClassToIW');

				assert('Dotink\Inkwell\IW::addLoadingStandard')
					-> using($app)
					-> with('IW', 'Dotink\Inkwell\IW::bogusTransformCallback')
					-> throws('Dotink\Flourish\ProgrammerException');
			},

			//
			//
			//

			'addRoot()' => function($config) {

				$app = new Inkwell\IW($config['root']);

				assert('Dotink\Inkwell\IW::addRoot')
					-> using($app)

					-> with('testing', 'external/testing')
					-> equals($config['root'] . DS . implode(DS, ['external', 'testing']))

					-> with('testing', '/tmp')
					-> equals('/tmp')

					-> with('testing', '/tmp/is/garbage/dir')
					-> throws('Dotink\Flourish\ProgrammerException');
			},


			//
			// Two roots are added, one relative, one absolute and then we test to make sure they
			// both come back as full directories paths, the relative one rooted in the
			// $config['root'] value which is passed to IW during instantiation.  Lastly we
			// make sure that a value which was not added returns just the $config['root']
			// value.
			//

			'getRoot()' => function($config) {

				$app = new Inkwell\IW($config['root']);

				$app->addRoot('testing', implode(DS, ['external', 'testing']));
				$app->addRoot('tmp', '/tmp');

				assert('Dotink\Inkwell\IW::getRoot')

					-> using($app)
					-> with('testing')
					-> equals($config['root'] . DS . implode(DS, ['external', 'testing']))

					-> with('tmp')
					-> equals('/tmp')

					-> with('value not added')
					-> equals($config['root']);
			},


		]
	];
}
