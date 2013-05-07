Although it's 100% possible to simply return or echo/output values from inside controllers and route actions, many times you will want to have a lot more control over what you send back over the wire.  The `Response` class in inKWell provides a clean way to specify exact status/code, mime type, and additional headers of your response.

## Contextual Response {#contextual_response}

The router context in inKWell provides the default `HTTP\NOT_FOUND` response to controllers and other router actions when they are executed.  The simplest way to provide a formal response is simply to invoke this from within your action as the return value:

```php
return $this['response'](HTTP\OK, 'text/plain', 'I got you a dollar.');
```

## Checking and Getting Response Information {#response_information}

If you need to know about the current state of the response there are a number of methods you can use to check or get various pieces of information.

Checking the status:

```php
$this['response']->checkStatus(HTTP\FORBIDDEN);
```

Cecking the code:

```php
$this['response']->checkCode(404);
```

Getting the status:

```php
$status = $this['response']->getStatus();
```

Getting the Code:

```php
$code = $this['response']->getStatus();
```

## Setting Headers {#setting_headers}

If you need to set additional or custom headers for your response you can do so also when invoking the response:

```php
return $this['response'](
	HTTP\NOT_ALLOWED,
	'text/plain',
	[
		'Allow' => 'GET, POST'
	],
	'That method is not allowed'
);
```

### One-by-One {#single_header}

You can also add headers more slectively throughout the code and ignore in the invocation.

```php
$this['response']->setHeader('X-Custom-Header', 'No idea what this does');

return $this['response'](HTTP\OK, 'application/json', json_encode($data));
```

### Removing a Header {#removing_headers}

If in the off chance you need to remove a header that was previously set, just set the value to NULL:

```php
$this['response']->setHeader('X-Custom-Header', NULL);
```

## Rendering {#rendering}

When you invoke or create a new response you will generally pass it a view of some sorts, whether it be a string, a [view object](./views), or something else.

The response class will make due attempts to render the view into a string.  If you are working with custom view objects, alternative templating systems, or any other non-standard views, you can add rendering methods to the configuration located in `config/default/rendering.php`.

The `'methods'` key contains a simple list which allows you to map certain object class's to specific methods on the object to complete rendering.

```php
'methods' => [
	'Dotink\Inkwell\View'   => 'make',
	'Dotink\Flourish\Image' => 'output'
]
```

## Caching {#caching}

Working with the response cache is an easy way to improve the performance of your site.  In addition to setting the appropriate headers for clients, it provides an easy mechanism for sending a response ASAP.

### Etags {#etags}

Etags are completely transparent in inKWell and will be generated for every response.  If APC is enabled the response object will store the current etag along with the cache id of the response.  Upon additional requests if the client supplies the `If-None-Match` header, the etag will be checked against the generated version.  If these match the response body will not be rewritten to the server side cache and the response will be automatically converted to a `304` (Not Modified).

### Aging {#cache_aging}

You can set the max age (the longest amount of time which a cached version is considred valid) using the `setAging()` method:

```php
$this['response']->setAging('3 Hours');
```

If you need to set a different aging for proxies add another parameter:

```php
$this['response']->setAging('3 Hours', '1 Hour');
```

### Expiring {#expiring}

If you need to expire the cache immediately simply do...

```php
$this['response']->expire();
```

### Other Cache-Control Options {#cache_control_options}

Making the response publically cacheable:

```php
$this['response']->setCache(CACHE\PUBLIC);
```

Keeping it private:

```php
$this['response']->setCache(CACHE\PRIVATE);
```

Public, but require proxy/cache to submit request to origin server before releasing:

```php
$this['response']->setCache(CACHE\PUBLIC, TRUE);
```

Absolutely do not cache at any level:

```php
$this['response']->setCache(CACHE\NO_STORE);
```

The response will also respect the `no-store` setting as it may have been sent by the client.  That is to say, if specified in the request, it will refuse to store the response in the cache.

### Sending a Cached Response (Future) {#sending_cached_responses}

You can automatically send a cached response by doing the following:

```php
$this['response']->sendCached();
```

If the cached response is expired, no action will be taken.  If the cached response is valid, however, a `Flourish\YieldException` will be produced.  You can catch this exception in the case of sub-requests and resubmit a request with modified headers if you prefer not to recieve a cached copy.

## List of HTTP Status Constants {#http_status_constants}

The full list of HTTP response constants is as follows, shown from the `include/constants.php` file:

```php
'HTTP\OK'             => 'Ok',
'HTTP\CREATED'        => 'Created',
'HTTP\ACCEPTED'       => 'Accepted',
'HTTP\NO_CONTENT'     => 'No Content',
'HTTP\BAD_REQUEST'    => 'Bad Request',
'HTTP\NOT_AUTHORIZED' => 'Not Authorized',
'HTTP\FORBIDDEN'      => 'Forbidden',
'HTTP\NOT_FOUND'      => 'Not Found',
'HTTP\NOT_ALLOWED'    => 'Not Allowed',
'HTTP\NOT_ACCEPTABLE' => 'Not Acceptable',
'HTTP\SERVER_ERROR'   => 'Internal Server Error',
'HTTP\UNAVAILABLE'    => 'Service Unavailable',
```

You can see each constant defines nothing more than the status code as it is often represented in human terms.  The full mapping of constants to codes as well as default bodies is located in the response class configuration in `config/default/library/response.php`.  If you need to modify codes or responses for whatever reason you can edit them here, or better yet, overload them in an [environment configuration](extending/multiple_environments).