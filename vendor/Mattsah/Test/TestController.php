<?php namespace Mattsah\Test 
{
	use Dotink\Inkwell;
	use Dotink\Flourish;
	use Dotink\Inkwell\HTTP;

	class TestController extends Inkwell\Controller
	{
		/**
		 * Initialize the class
		 *
		 * @param Dotink\Inkwell\IW $app The application instance loading the class
		 * @param array $config The configuration data for this class
		 * @param string $element The element ID for the class
		 */
		static public function __init($app, Array $config = array())
		{
			self::prepare(__CLASS__, function($controller) use ($app)) {
				$view = $app->create('view')->load(
					'html.php',
					[],
					[]
				);

				return [
					'view' => $view
				];
			});

			return TRUE;
		}


		/**
		 *
		 */
		public function hello($name = 'World')
		{
			$type   = $this->acceptTypes('text/html');
			$method = $this->allowMethods(HTTP\GET, HTTP\POST);

			$name   = $this['request']->get('name', 'string', $name);

			try {
				$status = $this->exec(HTTP\GET, '/user/[!:slug]', [
					'slug' => $user->getSlug()
				]);

				$this['view']->add('aside', $status);

			} catch (Flourish\YieldException $e) {}


			return $this['response']('not_found', $type, 'Hello ' . ucwords($name));
		}
	}
}
