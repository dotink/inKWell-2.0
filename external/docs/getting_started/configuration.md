# Configuration

## Core Configs

Configurations with a type of `Core` in inKWell can be referenced by using the `@<path basename>`
syntax.

```php
var_dump($app['config']->get('array', '@autoloading'));
```

The above code, for example, would dump out the configuration for the `autoloading.php` in the
configuration root.

Since `Core` configurations are designed to be extensible in a modular fashion, any other
configuration can signify that it implements those configuration features by typecasting itself
using the same syntax.

To avoid key conflicts with the configuration's primary type, the keys relevant to the core
configuration are then also nested within a key using the same name.

```php
return Config::create(['@autoloading'], [
	'@autoloading' => [

		//
		// @autoloading configuration options go here
		//

	]
]);
```

Although most core configs are at the root level, it is completely possible to have one in a
nested directory.

```php
return Config::create(['@library/custom_core'], [
	'@library/custom_core' => [

		//
		//
		//

	]
]);
```
