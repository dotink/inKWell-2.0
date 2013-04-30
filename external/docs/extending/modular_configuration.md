One of the most powerful aspects of inKWell (including 1.0) has always been its configuration system.  By using loosely typecasted configs it is possible for various components to aggregate information from highly separated modules.  This has not changed in inKWell 2.0, but has only been enhanced.

## Configuration Overview {#overview}

Configurations in inKWell are separate files which return an array of configuration information.  When the system initializes, all files in the configuration directory for an environment are loaded, their values returned, and keyed with a unique ID in a central configuration array.  The configuration as a whole also contains a section of configurations keyed by type wich a simply arrays of references to individual configurations which matched a given type when they were defined.

To create a configuration in inKWell you use the `Config::create()` method.

```php
<?php namespace Dotink\Inkwell
{
	return Config::create([], [

		//
		// Configuration elements go here
		//

	]);
}
```

An indvidual configuration file can be stored anywhere in the configuration directoy for the environment.  To see much more developed examples of these, check out the `config/default` directory which is applicable as a baseline for all environments.

### Typing a Configuration {#typing_configurations}

You may have noticed that the configuration elements are passed in as the second argument to the `create()` method.  Yet there is another empty array preceding this.  The first argument to `create()` is a list of configuration types.  For example, the configuration below is type-casted as a "library.""

```php
<?php namespace Dotink\Inkwell
{
	return Config::create(['Library'], [

		//
		// Configuration elements go here
		//

	]);
}
```

#### Built-In Configuration Types {#built_in_types}

Although not enforced in any way (other than it might break things or not work), there are two built-in configuration types which you should think long and hard before using.

- Core
- Library

In addition to built-in configuration types, inKWell consideres all non-alpha-numeric characters (such a symbols) to be reserved.  You'll see why shortly.

##### Core Configurations {#core_configurations}

Unlike other typed configurations, `Core` configurations do not necessarily have an expected data format.  Instead, a `Core` configuration indicates that the name of the file should be used to create a new dynamic configuration type.  Using the `config/default/routing.php` file we can see an example.

```php
return Config::create(['Core'], [

	'base_url' => '/',

	'actions' => [
		'/system_information' => 'phpinfo'
	],

	'handlers' => [

	]
]);
```

By using the `Core` configuration type, this identifies a new dynamic type called `@routing`.  A such, it is possible to extend the routing core configuration using any other configuration file in inKWell:

```php
return Config::create(['@routing'], [
	'@routing' => [
		'base_url' => '/forums',

		'actions' => [

			//
			// Forum Routes
			//

		]
	]
]);
```

**Note: Symbols such as '@', '#', '!' are reserved in configuration types for future dynamic typing.**

##### Library Configurations

Configurations typed as libraries have certain elements which carry meaning to inKWell.  Namely, the following keywords are used to specify information that can auto-configure the scaffolder and autoloader:

- class
- root_directory
- auto_load
- auto_scaffold

We will not go into how these function here, but suffice to say unless you are creating a base class or a modules which defines some dynamic behavior, you should avoid using the 'Library' key.

#### Custom Keys

Aside from those mentioned above, you can use whatever types you wish to group configurations.  The reaons for typecasting configurations are varied, but generally it can be used to set up some common behavior which multiple componets or other modules can use.

For example, if you were creating a worker queue you might define configs such as the following:

```php
return Create::config(['WorkerQueue'], [
	'job_name' => 'SendEmail',

	'operation' => 'Emailer::send'
]);
```

In the above example, the worker queue class (whatever it's called) could then query the configuration for all `WorkerQueue` typed configs and setup abstract job name to callback mappings.

## Querying Configurations

The configuration object is stored on the inKwell application instance.  For additional information on where the application instance is available, check out the [the inkwell interface](./inkwell_interface) and [console](/advanced/console) documentation.

To query configurations you can use the `getByType()` method.

```php
$worker_queue_configs = $app['config']->getByType('array', 'WorkerQueue');
```

You can then iterate over the configs to setup your class.

```php
foreach ($worker_queue_configs as $config) {
	if (isset($config['job_name']) && isset($config['operation'])) {
		$worker_queue->addOperation($config['job_name'], $config['operation']);
	}
}
```

You can also query specific elements.  The first argument to `getByType()` will indicate the type of the element you're requesting.  For entire configs, this is always `'array'`, but it will differ for specific elements.

```php
$job_names = $app['config']->getByType('string', 'WorkerQueue', 'job_name');
```