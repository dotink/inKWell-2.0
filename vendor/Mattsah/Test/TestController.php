<?php namespace Mattsah\Test 
{
	use Dotink\Inkwell;
	use Dotink\Flourish;

	class TestController extends Inkwell\Controller
	{
		public function hello($name = 'World')
		{
			$name = $this['request']->get('name', 'string', $name);

			return $this['response']('ok', 'text/plain', 'Hello ' . ucwords($name));
		}
	}
}