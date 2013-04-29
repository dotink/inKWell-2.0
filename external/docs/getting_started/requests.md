Requests in inKWell are encapsulated HTTP requests.  They are used internally to get information about the request which the user has performed as well as to make requests to other [controllers](./controllers).

## The Client (User) Request {#client_request}

The client request is constructed early on in the execution cycle and is passed to the router and in turn provided to actions via the router context.  Remember that for [controllers](./controllers) the router context is accessed on the controller itself:

```php
$request = $this['request'];
```

While for other [types of actions](./routing#actions) the context is passed as the first argument.

```php
'/hello/[!:name]' => function($context) {
	echo 'Hello ' . $context['request']->get('name');
}
```

The `'request'` element for the entry action will be a complete encapsulation of information normally accessed among PHP superglobals like `$_GET` and `$_SERVER` as well as some disparate functions.

## Working with Request Objects {#working_with_requests}

The examples below will use the request object as if we were inside a controller action, i.e. `$this['request']`.  It is important to note that regardless of the variable which is holding the request, the same methods apply.

### Checking Information {#checking_information}

Often times you don't need to get a particular piece of information but just need to check whether it's available and/or whether it's equal to something.  The request object provides a number of methods prefixed with `check` for this exact purpose.

Check whether a parameter is available:

```php
$this['request']->check('page');
```

Check whether a parameter has a value:

```php
$this['request']->check('page', 5);
```

Check whether a parameter has a value amongst many:

```php
$this['request']->check('page', [1, 2, 3, 4]);
```

Check the request method:

```php
$this['request']->checkMethod(HTTP\POST);
```

### Getting Information {#getting_information}

Most basic way to get a parameter/data value:

```php
$page = $this['request']->get('page');
```

Get some data, but ensure it is casted as a specific type:

```php
$page = $this['request']->get('page', 'int');
```

Get some data, ensure it's a specific type, and provide a default if it's not there:

```php
$page = $this['request']->get('page', 'int', 1);
```

Get the request method:

```php
$method = $this['request']->getMethod();
```

Get the most desired accept type (the mime type identifying the format of the response):

```php
$type = $this['request']->getBestAcceptType();
```

Get the best choice out of what you provide:

```php
$type = $this['request']->getBestAcceptType('html/text', 'application/json');
```

Get the most desired accept language:

```php
$lang = $this['request']->getBestAcceptLanguage();
```

Get the URL (object) for the request:

```php
$url = $this['request']->getURL();
```

### Array Input Data {#array_input_data}

While it's completely possible to get or check an entire array using the above methods which pertain to data, you can shorthand dereference arrays when you specify the data to get:

```php
$user_name = $this['request']->get('user[name]', 'string');
```

**Note that there are no internal quotes around the key, so it is the same as you would insert it in an HTML form.**

