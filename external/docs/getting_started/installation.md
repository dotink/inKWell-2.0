
Installing inKWell is as straightforward as it gets.  With version 2.0, inKWell was modified to be much more modular than the previous version so we now recommend composer as the primary method for installation.

## Requirements and Suggested Packages {#requirements}

We don't need much.

- PHP 5.4+
- Composer ([get composer](http://www.getcomposer.org))

If you wish to use pre-processed assets such as `.less` or `.dart` you may need to install additional components for [assetic](https://github.com/kriswallsmith/assetic).

## Installing with Composer {#installing_with_composer}

Once you have downloaded composer, depending on how you installed it and whether or not your operating system supports it you can run one of the two commands below to install inKWell:

```bash
composer create-project -s dev dotink/inkwell-2.0 <directory>
```

Or, if you were unable to perform a system-wide installation:

```bash
php composer.phar create-project -s dev dotink/inkwell-2.0 <directory>
```

Composer will install a number of requirements which inKWell makes use of by default, including but not limited to [Opus](http://www.github.com/imarc/opus), [Thrive](http://www.github.com/dotink/thrive), and a handful of different traits provided by .inK.

### Notes on Minimum Stability {#minimum_stability}

The `-s dev` option in composer commands indicates that we are willing to accept development stability.  This is important because inKWell is under active development.  Once we begin to move out of beta we will establish much more clearly defined versions for inKWell and its dependencies.

## Installing with Git {#installing_with_git}

While you will still need composer to resolve dependencies, you can boostrap your installation directly with git.  This is also useful if you plan to be swapping out some of the other dependencies and prefer to tweak them one by one before running `composer install`.

```bash
git clone https://github.com/dotink/inKWell-2.0.git <directory>
```

## Permissions {#permissions}

There are two directories in inKWell which are written to under the default configuration.

- `writable`
- `public/assets/cache`

The `writable` directory is a general directory used for various classes to store caches and/or uploaded files.  New directories and files will be created in here as needed.  The `public/assets/cache` directory is used for caching combined and pre-processed assets in the view.

Make sure these directories are writable by whatever user and/or group your webserver or PHP is running as.

## Test Your Installation {#test_your_install}

You can run a quick test of your installation by running the following command:

```php
php -S localhost:8080 -t <directory/public>
```

Then visiting [http://localhost:8080/system_information](http://localhost:8080/system_information) in your browser.  If everything goes well you should see a standard `phpinfo()` dump.

## Server Setup {#server_setup}

Setting up inKWell on your server is simple.  The `public` folder includes an `.htaccess` file which has everything you need for apache.  If you're using PHP-FPM or CGI, the included `.user.ini` file will set the appropriate flags for PHP as well and NGINX users can use the below code as a starting point.

**Note: If you're using apache you will need to ensure mod_rewrite is enabled, as well as `.htaccess` overrides**

### NGINX Config {#nginx_config}

```nginx
server {
		listen       80;
		server_name  <hostname>;

		root         <path_to_inkwell>/public;
		index        index.php;

		gzip         on;
		gzip_disable "MSIE [1-6]\.(?!.*SV1)";

		# Whether or not rewriting is enabled

		set $iw_rewrite_enabled 1;

		# Deny access to .hidden files, if Apache's document root
		# concurs with nginx's one

		location ~ /\. {
				deny  all;
		}

		location / {
				try_files $uri @inkwell;
		}

		# BEGIN INKWELL CONFIGURATION

		location @inkwell {
				if ($iw_rewrite_enabled) {
						rewrite ^/(.*)$ /index.php?$query_string;
				}
		}

		location ~ ^.+\.php {
				fastcgi_pass         127.0.0.1:9000;
				fastcgi_index        index.php;
				fastcgi_read_timeout 300;

				include fastcgi_params;

				fastcgi_param REWRITE_ENABLED  $iw_rewrite_enabled;
		}
}

```