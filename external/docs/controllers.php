## Preparing

Controller's use the Dotink Container trait to support dependency juggling and access.  Here's
a brief example of how this works:

```php
class Controller extends Dotink\Inkwell\Controller
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
		//
		// Initialization logic
		//

		self::prepare(__CLASS__, function($controller) use ($app)) {
			return [
				'view' => $controller->setup($app->create('view'));
			];
		});

		return TRUE;
	}

	/**
	 * Sets up our view
	 */
	protected function setup($view)
	{
		$this->acceptTypes('text/html');

		view->load(
			'html.php',
			[
				'scripts' => ['scripts/jquery.js'],
				'style'   => ['styles/inkling.css', 'styles/main.css'];
			],
			[
				'header' => 'site/header.php',
				'footer' => 'site/footer.php'
			]
		);

		return $view;
	}
}

```
