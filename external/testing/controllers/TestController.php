<?php

	use Dotink\Inkwell;
	use Dotink\Inkwell\HTTP;

	class TestController extends Inkwell\Controller
	{
		static private $app = null;

		/**
		 *
		 */
		static public function __init($app, Array $config = array())
		{
			self::$app = $app;
		}

		public function main()
		{
			$tests = [

				'Application is registered properly' => function() {
					return (self::$app instanceof Dotink\Inkwell\IW);
				},

				sprintf(
					'Writable directory %s is writable',
					self::$app->getWriteDirectory()
				) => function() {
					return (is_writable(self::$app->getWriteDirectory()));
				},

			];

			if ($this['request']->checkMethod(HTTP\POST)) {
				foreach ($tests as $key => $test) {
					$tests[$key] = $test();
				}
			}

			return $this['response'](HTTP\OK, Inkwell\View::create('html', [
				'staple' => 'tests/main.html'
			], [
				'tests'  => $tests
			]));
		}

		/**
		 *
		 */
		public function upload()
		{
			if ($this['request']->checkMethod(HTTP\POST)) {
				$file = $this['request']->get('foobar[foo][bar]', 'file');

				return $this['response'](HTTP\OK, Inkwell\View::create('html', [
					'staple' => 'tests/upload_show.html',
				], [
					'file' => $file
				]));

			} else {
				return $this['response'](HTTP\OK, Inkwell\View::create('html', [
					'staple' => 'tests/upload_get.html',
				]));
			}
		}
	}
