The router in inKWell 2.0 is designed from the ground up to offer improved flexibility and better request/response management across your site.

## The Basics {#basics}

The router configuration in broken into two parts in inKWell 2.0.  The first part is the `routing.php` core configuration file where you can define global routes.  The second is the actual router class configuration, which we'll go into more later.

The global routing configuration is in `config/default/routing.php` and should look something like this (stripped of comments and namespace):

```php
return Config::create(['Core'], [
	'actions' => [
		'/system_information' => 'phpinfo'
	]
]);
```

If you followed our [installation process](./installing), then you'll note the `/system_information` route here we used to test our install.  You can add routes similarly by simply adding new entries to the `actions` array:

```php
'actions' => [

	//
	// A new route
	//

	'/' => function() {
		return 'Hello World!';
	}
]
```

### Routes vs. Actions {#routes_vs_actions}

In inKWell routes are defined solely as the external mapping which triggers a specific action.  While it is often the case that a single route will map to a single action, this does not have to be so.  For now, it is easiest to think of the route the URL you're trying to create for your users, and the action as the code with which you plan to execute it.


## Routes {#routes}

Routes in inKWell are a composite of static information, tokens, and additional configuration elements.  For example [redirects](/getting_started/redirects) which are also considered to be a route are contextualized by a response code, however use many of the same concepts we'll see here.

### Tokens {#tokens}

Tokens are short notation which you stick into parts of your URL segments to signify that you want to parse that information into a request parameter.  Examples include...

A complete segment:

```php
'/hello/[!:name]' => function($context) {
	return 'Hello ' . ucwords($context['request']->get('name'));
}
```

An integer greater than 1:

```php
'/search/tag/[+:page]' => 'ContentsController::searchByTag'
```

And the wildcard:

```php
'/blog/[*:path]' => 'BlogController::show'
```

#### Cues {#token_cues}

The symbol on the left side of the token prior to the colon is called a **cue** in inKWell.  Cue's are built in matches which can be used shorthand for common parsing.  It is also possible to use a completly custom regular expression in place of a cue.

```php
'/news/[(breaking|editorials|stories):category]/[+:id]-[!:slug]' => 'ArticlesController::show'
```

#### Transformations {#token_transformations}

When a token is found in an action, its cue is called a **transformation** and it no longer serves the purpose of matching, but rather of transforming a previous match for placement in the action.

```php
'/[$:controller]/[$:action]' => '[uc:controller]Controller::[lc::action]'
```

The above example would convert an underscore or dash separated string in the `controller` or `action` segments into an UpperCamelCase and lowerCamelCase notation respectively, making the action being called dynamic as well.

### Quick Reference {#token_reference}

#### Cues Reference {#cue_reference}

| Cue | Description                                       | Example Route
|-----|---------------------------------------------------|----------------------------------------
| !   | Any character except a forward slash `/`          | `/users/[!:username]/friends`
| #   | Any integer with optional minus sign              | `/graph/[!:slug]/[#:posx]/[#:posy]`
| +   | Positive numbers greater than `1`                 | `/search/[!:query]/[+:page]`
| %   | A floating point number, with optional minus sign | `/map/[%:longitude]/[%:latitude]`
| $   | A valid PHP variable, class, or method name       | `/[$:class]/[$:method]`
| *   | A wildcard for all characters remaining           | `/[!:base_url]/[*:remainder]`

#### Transformations Reference {#transformation_reference}

| Cue | Description                                       | Example Target
|-----|---------------------------------------------------|----------------------------------------
| uc  | Converts a matching token to UpperCamelCase       | `[uc:class]::get`
| lc  | Converts a matching token to lowerCamelCase       | `[uc:class]::[lc:method]`
| us  | Converts a matching token to under_score          | `[us:function]`

## Actions {#actions}

While inKWell 2.0 does not yet support all the action types we have planned, it supports most of the basic ones that you'd need to use to create a useful web app or site:

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
'/hello' => function($this) {
	return 'Hello World! Nice to see you at ' . $this['request']->getURL();
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

Controllers are the most extensively developed classes which use the standard callback format.  Unlike other standard callbacks and closures, controllers free up the parameters on the method itself by taking the context via the `__construct()` method.  This allows for controller methods to be called directly without a formal request.

We highly recommend you check out the [controller page](/getting_started/controllers) to learn more.

### 0-Argument Functions {#functions}

Last, but not least, it is possible to simply specify a function which takes no arguments.  These are useful for routers which point to informational endpoints such as the default configured `'/system_information'` route which uses the internal function `phpinfo` for an action.