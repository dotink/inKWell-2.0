
Installing inKWell is as straightforward as it gets.  With version 2.0, inKWell was modified to be much more modular than the previous version so we now recommend composer as the primary method for installation.

## Requirements and Suggested Packages {#requirements}

We don't need much.

- PHP 5.4+

If you wish to use pre-processed assets such as `.less` or `.dart` you may need to install additional components so that assetic filters can work.

## Installing with Composer {#installing_with_composer}

First you will need to [get composer](http://www.getcomposer.org).  Once you have this, depending on how you installed it and whether or not your operating system supports it you will have to run either of the following:

```bash
composer create-project -s dev dotink/inkwell-2.0 <directory>
```

Or, if you were unable to perform a system-wide installation:

```bash
php composer.phar create-project -s dev dotink/inkwell-2.0 <directory>
```

Composer will install a number of requirements which inKWell makes use of by default, including but not limited to [Opus](http://www.github.com/imarc/opus), [Thrive](http://www.github.com/dotink/thrive), and [Assetic](http://www.github.com/kriswallsmith/assetic).

### Notes on Minimum Stability {#minimum_stability}

The `-s dev` option in the commands above indicates that we are willing to accept development stability.  This is important because inKWell is under active development.

## Installing with Git {#installing_with_git}

While you will still need composer to resolve dependencies, you can boostrap your installation directly with git.  This is also useful if you plan to be swapping out some of the other dependencies and prefer to tweak them one by one.

```bash
git clone https://github.com/dotink/inKWell-2.0.git <directory>
```

## Test Your Installation {#test_your_install}

You can run a quick test of your installation by running the following command:

```php
php -S localhost:8080 -t <directory/assets>
```

Then visiting [http://localhost:8080/system_information](http://localhost:8080/system_information) in your browser.  If everything goes well you should see a standard `phpinfo()` dump, however, do not be fooled -- this is running through inKWell's router.

## Server Setup {#server_setup}

Setting up inKWell on your server is simple.  The assets folder includes an `.htaccess` file which has everything you need for apache.  If you're using PHP-FPM or CGI, the included `.user.ini` file will set the appropriate flags as well.  NGINX users can use the below code as a starting point.

**Note: If you're using apache you will need to ensure mod_rewrite is enabled, as well as `.htaccess` overrides**

### NGINX Config {#nginx_config}

```nginx
server {
		listen       80;
		server_name  <hostname>;

		root         <path_to_inkwell>/assets;
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