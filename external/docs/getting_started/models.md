While inKWell 1.0 used the excellent Flourish ORM and Active Record implementation, 2.0 has gone in a new direction.  Instead of re-inventing the wheel, we decided to make use of Doctrine 2 for it's solid implementation of the data mapper pattern.

That said, we knew there'd be some things that users missed from the original implementation of models so we started a sub-project, [http://www.github.com/dotink/dub](http://www.github.com/dotink/dub), to give an active record feel to otherwise data mapper underpinnings.  With Dub, you get the best of both worlds.

## Connecting to Databases {#connecting}

As was the case with previous versions, you need only to edit a few lines in the configuration to connect to a database.  Let's look at the `database.php` config file in the "testing" environment's configuration path.

```php
return Config::create(['Core'], [
	'map' => [
		'default' => [
			'connection' => [
				'driver' => 'pdo_sqlite',
				'dbname' => NULL,
				'path'   => implode(
					DIRECTORY_SEPARATOR,
					[__DIR__, '..', '..', 'external', 'testing', 'sample.db']
				)
			]
		],
	],
]);
```

By filling in the information under the `connection` element, your models can start using the database immediately.  For additonal properties relevant to other databases check out the much fuller and well commented `config/default/database.php`.

## Database Resolution {#database_resolution}

Databases can be configured with a model namespace.  The default namespace is in the magic namespace used for inKWell aliases and is configured to `App\Model`.

```php
$customer = new App\Model\Customer();

$customer->setName('Matthew J. Sahagian');
$customer->setEmailAddress('info@dotink.org');
$customer->store();
```

Calling the `store()` method with no arguments will resolve the appropriate database name  based on the object's namespace and persist the model on the associated entity manager.  If no configured databases' model namespace matches that of the model itself, it will resolve to the `'default'` mapped entity manager.  So, even though `App\Model` is the default model namespace for the default database, the following would also attempt to persist on the entity manager associated with `'default'`:

```php
$customer = new Customer();

//
// do some stuff
//

$customer->store();
```

### Persisting vs. Writing {#persisting}

It is important to note that although inKWell provides the more convenient active record "style," is it still based on Doctrine 2's datamapper pattern.  The `store()` method doesn't do anything other than resolve best-guessed entity manager and attempt to persist the model on it.  This will trigger additional operations like validation, however, it does not actually write the data.

Indeed, the independent persistence layer allows you to explicitly persist models wherever you'd like.

```php
$customer->store('forums');
```

All actual write operations are still performed only when the appropriate entity managers are flushed.
