The router in inKWell 2.0 is designed from the ground up to offer improved flexibility and better request/response management across your site.

## The Basics {#basics}

The global routing configuration is in `config/default/routing.php` and should look something like this (stripped of comments and namespace):

```php
return Config::create(['Core'], [
	'actions' => [
		'/system_information' => 'phpinfo'
	]
]);
```

If you followed our [installation process](./installing), then you'll note the `/system_information` route we used for our initial test.  You can add routes similarly by creating new entries in the `actions` array:

```php
'actions' => [
	'/system_information' => 'phpinfo'

	//
	// A new route
	//

	'/' => function() {
		return 'Hello World!';
	}
]
```

The keys of the array define the route itself, while the values point to an executable action.

## Routes {#routes}

Routes in inKWell are a composite of static information, tokens, and additional configuration elements.  For example [redirects](/getting_started/redirects) which are also considered to be a route are contextualized by a response code, however use many of the same concepts we'll see here.

There is also a `'base_url'` key found at the root level of a routing configuration.  For the sake of this getting started guide, we will simply say that this is prepended to all the routes defined in that configuration.

### Tokens {#tokens}

Tokens are short notation which can be placed in a route to match given patterns and parse out variable data.  Tokens in inKWell routes are surrounded by `[]` (square brackets).  Examples include...

A complete segment (matches any character that is not a `/`):

```php
'/hello/[!:name]' => function($context) {
	return 'Hello ' . ucwords($context['request']->get('name'));
}
```

An integer greater than 1:

```php
'/search/tag/[+:page]' => 'ContentsController::searchByTag'
```

And the wildcard (matches all remaining characters including `/`):

```php
'/blog/[*:path]' => 'BlogController::show'
```

#### Cues {#token_cues}

The symbol on the left side of the token prior to the colon is called a *cue* in inKWell.  Cue's are characters, symbols, or custom regular expressions which define what format of information you want to match in place of the token.  The built-in cues use single characters to readily accomidate the most frequently used matching patterns.

The example below, however, shows a circumstance where you may wish to use a more complete regular expression:

```php
'/news/[(breaking|editorials|stories):category]/[+:id]-[!:slug]' => 'ArticlesController::show'
```

#### Transformations {#token_transformations}

Tokens can be placed in actions or target routes as well.  Here, the left component of the token, however, is called a *transformation*.  Rather than being for matching purposes, transformations are used to transform the string from the route into another format for placement in the action callback or target route.

```php
'/[$:controller]/[$:action]' => '[uc:controller]Controller::[lc::action]'
```

The above example would convert an underscore or dash separated string in the `controller` or `action` segments into an UpperCamelCase and lowerCamelCase notation respectively, making the action to call as dynamic as the route.

### Quick Reference {#token_reference}

#### Cue Reference {#cue_reference}

| Key | Description                                       | Example Route
|-----|---------------------------------------------------|----------------------------------------
| !   | Any character except a forward slash `/`          | `/users/[!:username]/friends`
| #   | Any integer with optional minus sign              | `/graph/[!:slug]/[#:posx]/[#:posy]`
| +   | Positive numbers greater than `1`                 | `/search/[!:query]/[+:page]`
| %   | A floating point number, with optional minus sign | `/map/[%:longitude]/[%:latitude]`
| $   | A valid PHP variable, class, or method name       | `/[$:class]/[$:method]`
| *   | A wildcard for all characters remaining           | `/[!:base_url]/[*:remainder]`

#### Transformation Reference {#transformation_reference}

| Key | Description                                       | Example Target Action
|-----|---------------------------------------------------|----------------------------------------
| uc  | Converts a matching token to UpperCamelCase       | `[uc:class]::get`
| lc  | Converts a matching token to lowerCamelCase       | `MyClass::[lc:method]`
| us  | Converts a matching token to under_score          | `[us:function]`

## Actions {#actions}

While inKWell 2.0 does not yet support all the action types we have planned, it supports everything that 1.0 did and is generally on par with other frameworks.  The various action types are as follows:

- Closures (Anonymous Functions)
- Standard Callbacks (`Class::method` and `[$object, 'method']`)
- 0-Argument Functions (`phpinfo`)

### Closures {#closure_actions}

```php
'/hello' => function() {
	return 'Hello World! Today is ' . date('m-d-Y');
}
```

With context:

```php
'/hello' => function($context) {
	return 'Hello World! Nice to see you at ' . $context['request']->getURL();
}
```

#### Limitations {#closure_limitations}

Closures can be great for rapidly prototyping certain actions, but they're not suitable for long term projects or serious development.  For starters, even if you split them up across multiple routing configs, they're not very maintainable.  Additionally, however, they...

- Prevent Configuration Serialization and Caching
- Cannot Use Custom Configuration Files
- Limit Modular Code Re-use

### Standard Callbacks {#callback_actions}

Standard callbacks are more flexible, allowing you to share information across instantiated objects and to establish custom configuration.  You can add public actions to existing classes if you're just looking for very simple integration.

```php
class Foo
{
	private $context = array();

	public function hello($context)
	{
		$port = $context['request']->getURL()->getPort();

		return 'Hello World! Nice to see you on port ' . $port;
	}
}
```

```
'/hello' => 'Foo::hello'
```

#### Controllers {#controllers}

Controllers are the most extensively developed classes which use the standard callback format.  Unlike other standard callbacks and closures, controllers free up the parameters on the method itself by taking the context via the `__construct()` method.


We highly recommend you check out the [controller page](/getting_started/controllers) to learn more.

### 0-Argument Functions {#functions}

Last, but not least, it is possible to simply specify a function which takes no arguments.  These are useful for routers which point to informational endpoints such as the default configured `'/system_information'` route which uses the internal function `phpinfo` for an action.