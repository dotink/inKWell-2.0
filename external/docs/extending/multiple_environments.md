As mentioned in the [confguration section](./modular_configuration), the `default` configuration is a base-line for your inKWell application.  Various modules or extensions for inKWell can install configuration files to this directory, however, rather than modifying these files directly, the suggested method for changing the configuration is to create new configuration directories and overload these values.

## Per-Environment Configurations {#per_environment_configs}

The final application configuration is an aggregate of the `default` configuration with any values from your custom configuration replacing those with the same keys (recursively).

To begin creating a custom configuration you simply need to create a new folder in the `config` directory.  From your application root, run:

```bash
mkdir config/<name>
```

For example, we can create three distinct configurations for a development, staging, and production environment with the following:

```bash
mkdir config/dev
mkdir config/stage
mkdir config/prod
```

### Overloading {#overloading}

The recommend approach to overloading is to explicitly overload only those values which you need to change.  For example, if you need to change the inKWell `display_errors` configuration in your production environment, just create the file `config/production/inkwell.php` with the following:

```php
<?php namespace Dotink\inKWell
{
	return Config::create([
		'display_errors' => FALSE
	]);
}
```

**Note: you do not need to re-specify the configuration types, although you can certainly add new ones if you need to.**

You can overload any configuration value equally by creating a file in the same location relative to your configuration directory and returning a configuration with the overloaded values in the same key structure.

### Using the Environment's Configurations {#using_a_config}

The simplest way to use a given configuration for a given environment is to configure it in your server configuration.  In apache this is usually done with the `SetEnv` directive.

```apache
SetEnv IW_CONFIG <environment>
```

For NGINX users you will usually want to add the following to an appropriate location block:

```php
fastcgi_param IW_CONFIG <environment>;
```