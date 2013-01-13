<?php namespace Dotink\Lab {

	use Dotink\Parody;

	return [

		'setup' => function($config) {
			needs($config['app_root'] . DS . 'includes/core.php');

			Parody\Mime::define('App\Text');
		},

		'cleanup' => function() {

		},

		'tests' => [

			////////////////////////////////////////////////////////////////////////////////////////

			'transformClassToIW()' => function() {

				Parody\Mime::create('App\Text')
					-> onCall('create') -> expect('WTF' . DS . 'Yo') -> give(function($mime) {
						return $mime
							-> onCall('underscorize') -> give('wtf' . DS . 'yo')
							-> resolve();
					});

				assert('Dotink\Inkwell\IW::transformClassToIW')
					-> with('\WTF\Yo\Bob')
					-> equals('wtf/yo/Bob.php');

				assert('Dotink\Inkwell\IW::transformClassToIW')
					-> with('WTF\Yo\Bob')
					-> equals('wtf/yo/Bob.php');

			},
		]
	];
}
