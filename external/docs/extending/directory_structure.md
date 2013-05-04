The directory structure in inKWell is designed to provide a clear distinction between major parts of the framework, your application, and other resources like third-party libraries, tests, docs, etc.

## The Application Root {#application_root}

Unlike a lot of other frameworks, the application root in inKWell is, by default, the root-most directory of the distribution.  There is no separate `app`.

Whenever an instance of inKWell is created, the app root can be passed directly to that instance at instantiation time.  Thankfully, we do no leave creating your own instances up to you.  There are two ways of bootstrapping and executing inKWell out of the box.

### Web Bootstrap (index.php) {#web_bootstrap}

The web bootstrapping process begins when your server or php-cgi/fpm calls the `index.php` file in the document root.  The suggested

## Default Structure {#default_structure}

At the root level of the framework, each folder containing runtime usable code is designed to fall into one of the following categories:

- Framework
- User Developed
- Third-Party

In addition to folders containing code, there are several "common" folders or folders which are more applicable to your final inKWell instance as a whole.

### Framework Code {#framework_code}

Framework-centric code is stored in two primary folders:

- `includes`
- `library`

### User Developed Code {#user_developed_code}

User developed code, depending on the type of code, can be found in one of the following:

- `public`
- `user`

### Third-Party Code {#third_party_code}

Lastly, although this is mostly due to composer, all third-party code which is not necessarily related to the framework is located in the `vendor` directory.  This is generally kept as composer modules and handled by composer's autoloader.