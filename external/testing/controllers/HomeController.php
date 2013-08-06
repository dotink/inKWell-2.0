<?php

	use Dotink\Inkwell;
	use Dotink\Inkwell\HTTP;

	class HomeController extends Inkwell\Controller
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
		public function main()
		{
			return $this['response'](HTTP\OK, Inkwell\View::create('html', [
				'staple' => 'home.html',
			]));
		}
	}
