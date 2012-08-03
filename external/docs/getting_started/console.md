## Using the Console

The inKWell console is a robust interface for your application, as well as for PHP itself.  Unlike
many other frameworks, it is more than a command line tool.  It is, instead, a full-on interactive
PHP shell.

### The Defaul $app

When the console initializes, it will load a new instance of inKWell with the default configuration,
or, if you have modified the `includes/init.php` file, whatever configuration is determined by its
logic.  This instance is available in the console as the $app variable.

If, for example, you wanted to cache your app's configuration, you can do the following:

```
$app['config']->write();
```

Keep in mind that anything you type into the console with the exception of the escaped internal
commands must be valid PHP to work (semi-colons and all).

### Using PHP Via the Console

The below examples give you an idea of what's available, these show both input and output:

```
[/home/matts/inkwell][01]# echo 'test';
test
```

```
[/home/matts/inkwell][01]# foreach (['apple', 'orange', 'grape'] as $fruit) {
[/home/matts/inkwell][02]# echo md5($fruit) . "\n";
[/home/matts/inkwell][03]# }
1f3870be274f6c49b3e31a0c6728957f
fe01d67a002dfa0f3ac084298142eccd
b781cbb29054db12f88f08c6e161c199
```

```
[/home/matts/inkwell][01]# \> ..
[/home/matts/][01]#
```