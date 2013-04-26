In addition to routes which call actions, there is another type of routing which can be done: redirects.  Redirects work exactly the same as routes although instead of pointing to a callable action, they point to the replacement URL.

## Configuration

The redirect configuration is found in `config/default/redirects.php`.  You will notice unlike routes, it is keyed first by the HTTP status code to use for the redirect:

```php
return Config::create(['Core'], [

	//
	// Permanent redirects.
	//

	301 => [

	],

	//
	// Temporary redirects
	//

	307 => [

	]
]);
```

## When to Use {#when_to_use}

> Cool URLs don't change - *Christian Heilmann*

If you've already got a fairly popular site, or even if you just tend to have pretty good search ranking and/or a lot of references going to your site that you don't want to update, you might be skeptical about changing your URLs too much.

However, redirects because they are processed before other routes are also useful for intercepting page requests.  This is particularly true in the case of temporary redirects.

## Examples

Changing your blog path where you now use the slug as a unique or primary key:

```php
301 => [
	'/blog/[+:id]-[!:slug]' => '/articles/[slug]'
]
```

CISPA Blackout:

```php
302 => [
	'/[*:who_cares]' => 'http://fuckcispa.com/'
]
```


