## Directory Structure

The directory structure in inKWell is designed to promote modularity, re-usability, and portability
of code.  In addition, each directory's existence is justified with very specific logic.  That
said, feel free to change whatever you like... it's really easy!

### Root Directories

Root directories in inKWell (yes there's more than one), are nodal points which define specific
patterns when the system is looking for particular types of resources.  These directories are
added to the application instance and can be retrieve using the `getRoot()` method.

```
[/home/matts/inkwell][01]# echo $app->getRoot('config');
/home/matts/inkwell/config
```

The above example would get the configuration root.  While this may look like sleight of hand,
perhaps just appending the argument to the obvious application root, if we were to modify the
`init.php` file to load the configuration from a completely different directory, we would see
very quickly how the above would change.

Because inKWell understands keyed root directories, you will often times see terminology such as,
"What is your configuration root set to?" or "You can easily move your view root by..."

It is also quite common to see that directories in inKWell are relative to these roots.  For
example, looking at the controller configuration in `config/default/library/controller.php` we
can see the following:

```php
'root_directory' => 'user/controllers'
```

*Note that we do not specify '/user/controllers' with a preceding slash or `user/controllers/`
with a trailing slash.*

This directory is what we would call our "controller root" and is relative to the "application
root".

### The Application Root

The application root is established when inKWell initializes.  It is the directory which is
passed to the `IW::init()` method as the first argument.  How it is determined, depends on where
execution begins.  If you are executing from the console, the application root is determined to
be the directory in which the console resides.  If, however, you are passing through the
`index.php` file via a web server the application root is determined by moving backwards until the
`includes` directory is found.

Strictly speaking, the application root is that which contains the `includes` folder.

## Default Directories

Below you will find a list of directories (relative to the application root) that exist and may be
populated by inKWell along with notes on each.

### assets (Dynamic)

The `assets` directory is designed to contain web-published assets.  In short, it is your
filesystem based document root, and should be the actual document root that your server points
to.  This directory is also the default writable directory.

#### Default Subdirectories

- scripts : Should conttain any client consumable script (usually javascript)
- styles : Should contain any client consumable styles (usually css)

#### Definition

The only dependency on this directory for inKWell is as the default writable directory.  You can
change its location by editing the `config/default/inkwell.php` file and changing the value of
the `write_directory` key.  Your server configuration is up to you!

### config (Static)

The default configuration root.  The configuration root contains separate subdirectories for each
version or instance of configurations you might use.

#### Default Subdirectories

- default : The default configuration

#### Definition

This can be changed or dynamically determined in the `includes/init.php` file.  It is the second
argument passed to the `config()` method on the `$app` instance.

### external (Not Required)

External is used to store various pieces related to your app, but not explicitly part of inKWell
or the application logic itself such as SQL schemas, docs, tests, etc.

#### Default Subdirectories

- docs : inKWell Documentation
- sql : Database schemas and migrations
- tests : Unit test framework and tests
- utils : inKWell toolchain

#### Definition

The location of this directory shouldn't matter in the least to inKWell.

### includes (Required)

The `includes` directory contains various non-class include files that are critical to the inKWell
bootstrapping process.  This includes the `IW` class itself which is actually defined in the
`includes/core.php` file.

#### Definition

Any entry point to inKWell will be concerned with the location of this folder.  Curently those
entry points are the `.console` PHP script and the `assets/index.php` script which both look to
the `includes` directory for the `init.php` file and others.  You can change it in both places,
however, future versions of inKWell may have other entry point mechanisms that would not be
functional.

### library (Static)

The `library` directory contains inKWell's core library.  This includes Flourish and all the core
classes that come with inKWell.

### user (Dynamic)

The user directory is designed for any user created components that are specific to inKWell, i.e.
would not be generally portable due to framework dependency.  This includes controllers, models,
views, as well as services, tasks, and scaffolding templates.

### vendor (Dynamic)

The `vendor` directory is designed for highly portable third-party code.
