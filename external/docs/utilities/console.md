## Using the Console

The inKWell console is a robust interface for your application, as well as for PHP itself.  Unlike
many other frameworks, it is more than a command line tool.  It is, instead, a full-on interactive
PHP shell.

### The Default $app

When the console initializes, it will load a new instance of inKWell with the default configuration,
or, if you have modified the `includes/init.php` file, whatever configuration is determined by its
logic.  This instance is available in the console as the `$app` variable.

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

### Internal Commands

Internal commands are a single character preceded by a `\` and then followed by a number of
arguments.

#### Clear Screen: \c

*Note: This does not work properly on windows at the moment*

#### Execute System Command: \e <command>

```
[/home/matts/inkwell][01]# \e ls -tla
total 184
drwxrwxr-x  4 matts matts  4096 Sep  1 03:47 assets
drwxrwxr-x  2 matts matts  4096 Sep  1 03:36 includes
drwxrwxr-x  9 matts matts  4096 Sep  1 03:08 .git
drwxrwxr-x  2 matts matts  4096 Sep  1 03:08 vendor
drwxr-xr-x 10 matts matts  4096 Sep  1 03:08 .
drwxrwxr-x  5 matts matts  4096 Sep  1 03:08 library
drwxrwxr-x  6 matts matts  4096 Sep  1 03:08 user
drwxrwxr-x  3 matts matts  4096 Sep  1 03:08 external
-rwxrwxr-x  1 matts matts   631 Sep  1 03:08 inkwell
-rwxrwxr-x  1 matts matts   167 Sep  1 03:08 inkwell.bat
drwxrwxr-x  3 matts matts  4096 Sep  1 03:08 config
-rw-rw-r--  1 matts matts 69040 Sep  1 03:08 LICENSE.txt
-rw-rw-r--  1 matts matts    59 Sep  1 03:08 README.md
-rw-rw-r--  1 matts matts 12130 Sep  1 03:08 .console
-rw-rw-r--  1 matts matts   105 Sep  1 03:08 .gitmodules
drwxrwxr-x  7 matts matts  4096 Sep  1 03:08 ..
```

#### Enter Non-Interactive Mode: \m

Non interactive mode allows you to leave the interactive mode and type or paste in a long block
of PHP code that will not be executed until the end of file sequence is triggered (<ctrl> + D on
unix systems and <ctrl> + Z on windows).  Once executed the program will exit completely.

#### Quit the Program: \q

The console intentionally does not quit when exit() is called in interactive mode.

#### Reset the Program: \r

This will clear all existing context (variables, defined classes and functions, etc) and restart
the console without actually exiting and re-running the command.

#### Change Directory: \> <directory>

```
[/home/matts/inkwell][01]# \> ..
[/home/matts/][01]#
```

#### Display Help: \?

```
[/home/matts/Dropbox/Code/inkwell-2.0][01]# \?

\c - Clear the Screen
\e - Execute a System Command
\m - Enter Non-Interactive mode
\q - Quit the Program
\r - Reset the Program
\> - Change Directory
\? - Display this Message
```

### Known Issues

Currently the console no longer runs non-syntax error checks on code.  This means the console will
reset every time a fatal error is incurred.  This will be added back in a later release with much
more robust checks.

#### Windows Specific

1. Clearing the screen does not work
2. Syntax checking is slow
