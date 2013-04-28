As we already mentioned in the [routing documentation](./routing), controller methods are the most advanced actions you can point a route to.  They provide a number of methods which assist you with controlling the request and also cleanly wrapping the router context.

## Creating a Controller

Controllers can exist in whatever namespace you'd like, but there's a few namespaces we definitely suggest you alias.  The most basic and stripped down controller class will look something like this:

```php
<?php namespace Vendor\Project
{
	use App;
	use Dotink\Inkwell;
	use Dotink\Inkwell\HTTP;

	class MyController extends Inkwell\Controller
	{
	}
}
```

### Controller Root Directory {#root_directory}

Any controller class which ends with `Controller` (e.g. `CustomController`) will have an attempt made by the inKWell autoloader to be loaded from the controller root.  The default controller root is in `user/controllers` and uses the [inKWell autoloading standard](#).  This means that the namespace is first "underscorized" into a path before loading.  So a controller whose fully qualified class name is `Vendor\MyProject\HomeController` will attempt to autload from `user/controllers/vendor/my_project/HomeController.php`

### Adding actions {#adding_actions}

Each public method represents a routable action.  For security purposes methods beginning with `__` such as the magic methods `__get()` and `__set()` are not allowed.  We can add an action simply:

```php
class MyController extends Inkwell\Controller
{
	//
	// You can route to this method
	//

	public function show()
	{

	}

	//
	// You cannot route to this method
	//

	private function doSomething()
	{

	}
}
```

## Accessing the Router Context {#router_context}

One thing to note is that the `__construct()` method on a controller cannot be overloaded.  Controller's use .inK's `container-trait` to store the router context.  The router context for other actions is passed directly to the method, however, for a controller the context is stored directly on the class and accessible via `ArrayAccess`.

```php
public function show()
{
	//
	// Here's what's available in the context
	//  - The router that got us here
	//  - The request that is being made
	//  - The current response
	//

	$router   = $this['router'];
	$request  = $this['request'];
	$response = $this['response'];
}
```

This allows us to call methods directly on any of these key components:

```php
$this['request']->redirect('http://laravel.com', HTTP\REDIRECT_PERMANENT);
```

## Controller Request Flow {#controlling_flow}

The examples below can be done inside the context of any controller action and are simple ways to perform common control functions.

### Allow Methods {#allow_methods}

```php
$this->allowMethods('GET', 'POST');
```

This will also return the current method:

```php
$current_method = $this->allowMethods('GET', 'POST');
```

The above method will trigger an `HTTP\NOT_ALLOWED` error response automatically and immediately if the request method is not on the list.

### Checking the Entry Controller and Action {#checking_entry_actions}

It is possible to make both internal and external sub-requests in inKWell.  For this reason if you're creating a hierarchical pattern of controllers or MVC triads as a whole, you may need to frequently check whether or not the action you're on is the original action requested by the user.

```php
if ($this->checkEntryAction('show')) {
	echo 'You have called the show action';
}
```

You can also provide a class to check other controllers:

```php
if ($this->checkEntry(__NAMESPACE__ . '\OtherController', 'show')) {
	...
}
```

### Triggering Errors {#triggering_errors}

```php
$this->triggerError(HTTP\NOT_AUTHORIZED);
```

Or add a custom message/view

```php
$this->triggerError(HTTP\NOT_AUTHORIZED, 'Stop Hacking!');
```

## Outputting {#outputting}

The recommended method for getting a controller to output to the screen is to use the return value of the action.

```php
public function show()
{
	return 'Actually, nothing to see here.';
}
```

However, inKWell also allows another method, simple echoing.  Despite that the return value is the recommended method, if your controller action produces any output while running, this output will be chosen **in place of** the return value.  In both cases, for simple actions inKWell will send either a 404 (The default response) or a 200 (if you provide output).

```php
public function show()
{
	//
	// This will result in a 404
	//

	return NULL;
}
```

```php
public function show()
{
	//
	// This will result in the var dump being shown
	//

	var_dump($this['request']);

	return 'My default response';
}
```

### Content-Type, Headers, etc. {#advanced_responses}

The above method shows a simple way to produce output or errors to the screen.  Although inKWell will attempt to determine the appropriate content types of these types of responses, it is often times better to have full control over content type, various headers, and/or other response parameters.

You can achieve this by manipulating the current response:

```php
public function show()
{
	return $this['response'](HTTP\OK, 'text/html', 'Plain text but will be sent as HTML');
}
```

To understand the full extent of control provided by responses, [take a look at their documentation](./responses).