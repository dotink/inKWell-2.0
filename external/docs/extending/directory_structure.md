The directory structure in inKWell is designed to provide a clear distinction between major parts of the framework, your application, and other resources like third-party libraries, tests, docs, etc.

## Default Structure {#default_structure}

At the root level of the framework, each folder containing runtime usable code is designed to fall into one of the following categories:

- Framework
- User Developed
- Third-Party

In addition to folders containing code, there are several "common" folders or folders which are more applicable to your final inKWell instance as a whole and may contain non-critical code or supporting files for any of the classifications above.

### Framework Code {#framework_code}

Framework-centric code is stored in two primary folders:

- `includes`
- `library`

Developers using inKWell for their applications and websites should not need to venture into either of these folders.  If you're trying to improve inKWell itself, these are the folders you want to look in.

#### Includes Folder {#includes_folder}

The `includes` folder contains everything necessary for creating a new instance of inKWell and executing it.  In fact, the inKWell class itself (IW) is contained in this folder under the `core.php` file.  It is the only *necessary* include to begin working with inKWell.

Additional files contain wrappers and bootstrap code that is used to set up the application instance in the context of inKWell as a framework and not just an application class.  This code includes the following and more:

- Providing baseline autoloader mappings
- Registering classes for dependency injection
- Loading the configuration
- Creating initial request, response, and router objects

#### Library Folder {#library_folder}

The `library` folder is used to store the default components which inKWell uses on everything from routing to text transformation.  It is important to note that the vast majority of code within the `library` folder is separate packages with the ultimate intention that it will all be separate packages.

### User Developed Code {#user_developed_code}

User developed code, depending on the type of code, can be found in one of the following:

- `public`
- `user`

These are the two folders which developers using inKWell for their project will be most concerned with.  Here is where users can add everything from controllers to javascript.  Modules, which is just other user developed code serving a self-contained purpose, are also installed into these folders.

#### The Public Folder {#public_folder}

The `public` folder is the default document root as suggested by inKWell installation and configuration.  It contains an `index.php` file capable of bootstrapping inKWell as well as the `assets` directory which provides a default directory structure for adding assets like javascript and css.

**NOTE: Both the provided `.htaccess` file and the example NGINX configuration forbid access to hidden files and folders, but you should never put non-client code or private information in the `public` folder**

#### The User Folder {#user_folder}

The `user` folder is the meat of your application / website.  It contains subdirectories for controllers, models, and views, and in many cases may need to be the only folder a developer need open for 90% of their development.

The other 10% will likely be copying assets to `public` and some light configuration work.

##### Namespacing and Loading Standards {#namespacing_user}

It is good practice to create a namespace based directory structure for your various components.  The suggested namespace for website specific code is to inverse the domain levels and use those.  For example, if your website was `subdomain.example.com` you would place all controllers in `controllers/Com/Example/Subdomain`.

Any component of a website which may be treated as it's own module, for example, forums, can have a more generic namespace which would allow for cross-domain use.  Instead of `controllers/Com/Example/Subdomain/Forums` you might have `controllers/Vendor/Forums`.

It is also important to note that although you can add autoloader mappings for your user developed components that core library components like `Controller` will have a default mapping based on the [inKWell autoloading standard](./auto_loading).

### Third-Party Code {#third_party_code}

Lastly, although this is mostly due to composer, all third-party code which is not necessarily related to the framework is located in the `vendor` directory.  This is generally kept as composer modules and handled by composer's autoloader.