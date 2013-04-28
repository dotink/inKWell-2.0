Although it's 100% possible to simply return or echo/output values from inside controllers and route actions, many times you will want to have a lot more control over what you send back over the wire.  The `Response` class in inKWell provides a clean way to specify exact status/code, mime type, and additional headers of your response.

## Contextual Response {#contextual_response}

Within an inKWell controller or router action, the context contains the default response established by the router.  For purposes of routing, this is an `HTTP\NOT_FOUND` response.

You don't need to create a new response for your action, but can simply modify this one by invoking it (example from in a controller action):

```php
return $this['response'](HTTP\OK, 'text/plain', 'I got you a dollar.');
```

## Checking and Getting Response Information {#response_information}

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

It is possible to add headers when you invoke a response:

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

In addition to adding them in bulk, you can add them slectively throughout the code and leave the argument out completely:

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

When you invoke or create a new response you inevitably pass it a view of some sorts.  The response class will attempt to render this view down to a string before responding.  If you do not provide an explicit mime-type then it will also cache this view and determine the appropriate mime type before sending it out.

In order to render views, the response class needs to know the various methods to call on view objects.  This configuration can be found in the `config/default/rendering.php` file and is simply a map of classes to methods.

This allows you to use alternative view classes, templating engines, etc, more readily.

## Caching (Future) {#caching}

**Note, inKWell currently caches views for mime-type resolution only in the event no type is specified, the features noted below are still in development.**

Working with the response cache is an easy way to improve the performance of your site.  In addition to setting the appropriate headers for clients, it provides an easy mechanism for sending a response ASAP.

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

### Sending a Cached Response {#sending_cached_responses}

You can automatically send a cached response by doing the following:

```php
$this['response']->sendCache();
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