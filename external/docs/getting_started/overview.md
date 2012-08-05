## Welcome to inKWell

Compared to the previous version of inKWell (1.0), inKWell 2.0 represents a major shift in how the
framework operates.  It is **way** more object oriented and provides modern facilities to make such
development a joy.

### Code Samples

Registering a factory:

```
$app->register('url', 'Dotink\Flourish\URL', function($url = NULL) {
	return new Dotink\Flourish\URL($url);
});
```

Creating new instances:

```
$url = $app->create('url', [], 'http://www.example.com');
```

Extensible Auto Loading Standards and Mappings:

```
	'standards'  => [
		'CUSTOM' => 'Vendor\Package\Autoloader::transformClass'
	],

	'map' => [
		'Vendor\*' => 'CUSTOM: includes/special/extensions'
	]
```

Appception:

```
$new_app = $app->create();
```

