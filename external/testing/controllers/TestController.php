<?php

	use Dotink\Inkwell;
	use Dotink\Inkwell\HTTP;

	class TestController extends Inkwell\Controller
	{
		/**
		 *
		 */
		static public function __init($app, Array $config = array())
		{
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
