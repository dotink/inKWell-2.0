As we already mentioned in the [routing documentation](./routing), controller methods are the most advanced actions a route can execute.  Controllers provide additional methods for modifying the request flow and free up the reception of the router context so your method arguments can be used for other purposes.

## Creating a Controller

Controllers can exist in whatever namespace you'd like, but there's a few namespaces you will most likely want to alias.  A basic and stripped down controller class will look something like this:

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

The controllers in inKWell are stored under the `user/controllers` directory by default.  This root directory is configurable in the `config/default/library/controller.php` file.  The directory structure within the controller root directory is based on the IW loading standard which is similar to PSR-0 with the following differences:

- Underscores are not converted to directory separators
- Any class ending with `Exception` wil be loaded from an 'Exceptions' sub-folder.
- Any class ending with `Interface` will be loaded from an 'Interfaces' sub-folder.
- Any class ending with `Trait` will be loaded from a 'Traits' sub-folder.

### Adding actions {#adding_actions}

Each public method represents a routable action.  For security purposes methods beginning with `__` such as the magic methods `__get()` and `__set()` are not allowed.  All other public methods, however, are fair game.

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

The `__construct()` method on the parent controller class is `final`.  Controllers us .inK's [container-trait](http://www.github.com/dotink/container-trait) to store the router context.  This means that elements in the router context can be accessed directly on the object.

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
Although the above example shows us additionally assigning these components to variables within the action, this is for explanation purposes only.  You can call methods directly on the elements using the array notation.

```php
$this['request']->redirect('http://laravel.com', HTTP\REDIRECT_PERMANENT);
```

## Controller Request Flow {#controlling_flow}

The examples below can be done inside the context of any controller action and are simple ways to perform common control functions.

### Allow Methods {#allow_methods}

```php
$this->allowMethods(HTTP\GET, HTTP\POST);
```

This will also return the current method in the event you need to check it's value futher down in the controller logic.

```php
$current_method = $this->allowMethods(HTTP\GET, HTTP\POST);
```

If the current request method is not in the list of allowed methods you supplied as arguments, this method will automatically modify the current response to `HTTP\NOT_ALLOWED`.  Additionally, it will set and appropriate `Allow` header and trigger a `YieldException` so that the router returns the response immediately.

### Checking the Entry Controller and Action {#checking_entry_actions}

It is possible to make both internal and external sub-requests in inKWell.  For this reason if you're creating a hierarchical pattern of controllers or MVC triads as a whole, you may need to frequently check whether or not the action you're on is the original action requested by the user.

```php
if ($this->checkEntryAction('show')) {
	echo 'You have called the show action';
}
```

You can also provide an explicit class if you're checking whether a method on another controller is the entry point.

```php
if ($this->checkEntry(__NAMESPACE__ . '\OtherController', 'list')) {
	...
}
```

### Triggering Errors {#triggering_errors}

You can trigger an error response at any time during execution to immediately stop the execution of the action and send the appropriate error response.

```php
$this->triggerError(HTTP\NOT_AUTHORIZED);
```

Or add a custom message/view:

```php
$this->triggerError(HTTP\NOT_AUTHORIZED, 'Stop Hacking!');
```

### Executing Requests (Future)

```php
$this->exec('http://www.google.com', new Request(HTTP\GET, 'text/html', [
	'q' => 'best php mvc framework'
]));
```


## Outputting {#outputting}

The recommended method for getting content in the response body is to return the content or view object from the controller.

```php
public function show()
{
	return 'Actually, nothing to see here.';
}
```

However, in some cases you may wish to make your controllers output information using `echo` or `include`.  As such, any output from a controller action is buffered and used as the response body *in place of* a return value if it occurs.

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

It is also possible to return `NULL` which will result in an `HTTP\NOT_FOUND` response, or an empty string or `FALSE` which will result in an `HTTP\NO_CONTENT` response.

This will result in a 404:

```php
public function show()
{
	return NULL;
}
```

This will result in a 204:

```php
public function show()
{
	return '';
}
```

### Content-Type, Headers, etc. {#advanced_responses}

When echoing or returning values as shown above, inKWell will try to determine the content type of your output automatically.  For simple strings, this is usually `text/plain`, however, if you returned the contents of an HTML file it would send the requisite `Content-Type: text/html; charset=utf-8` header.

Despite this behavior, it is often better to have full control over content type, headers, and/or other response parameters.  You can achieve this by [using response objects](./responses).