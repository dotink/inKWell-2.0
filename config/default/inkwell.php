<?php namespace Dotink\Inkwell
{
	return Config::create(['Core'], [

		//
		// The active domain name for the site.  This can handle automatic
		// redirection if the site is hit from an alias (i.e. throw away your)
		// apache/nginx redirects.  If it's null, then no redirection will
		// occur.
		//

		'active_domain' => NULL,

		//
		// The execution mode determines various aspects of operation.  Valid execution modes
		// are currently 'development' and 'production'.  Other settings, when set to NULL
		// will have varying defaults based on the execution mode.
		//

		'execution_mode' => 'development',

		//
		// This is the writable directory where caches, file uploads, images, etc. can be stored.
		// iw::getWriteDirectory() will supply this, or a sub-directory of this.
		//

		'write_directory' => 'writable',

		//
		// Here you can configure whether or not to display errors, or e-mail
		// them to you.  During development you will likely want to keep
		// display_errors set to TRUE, while once in production you may
		// wish to set the error_email_to to your e-mail address.
		//

		'display_errors' => NULL,
		'error_level'    => E_ALL & ~E_STRICT,
		'error_email_to' => NULL,

		'cache' => [
			//
			// Valid types include 'apc', 'file', 'directory', 'memcache', 'redis', 'xcache'
			//

			'type' => 'apc',

			//
			// For 'file' and 'directory' the datastore is a path.  For 'memcache' and 'redis' it
			// is the class of the appropriate object to insantiate, e.g. 'Memcached'
			//
			// 'data_store' => ''
			//

		],

		//
		// Default timezones follow the standard PHP notation, a list of
		// these can be located here: http://php.net/manual/en/timezones.php
		//

		'default_timezone' => 'America/Los_Angeles',

		//
		// Date formats can be added for quick reference when using dates
		// returned by the system.  Example being that if you had a column
		// in a database which was a date and wanted it to be represented
		// in a particular format you could do something like this:
		//
		// $user->prepareLastAccessedTimestamp('access_timestamp')
		//

		'date_formats' => [
			'console_date'      => 'M jS, Y',
			'console_time'      => 'g:ia',
			'console_timestamp' => 'M jS, Y @ g:ia'
		],

		//
		// Just-In-Time class aliases.  These classes will exist in the magic namespace 'App'.
		// So for example if you register an alias of 'Text' => 'Vendor\Project\Text' it will
		// actually need to be used as 'App\Text'.
		//

		'aliases' => [
			'Core'      => 'Dotink\Flourish\Core',
			'Date'      => 'Dotink\Flourish\Date',
			'Directory' => 'Dotink\Flourish\Directory',
			'File'      => 'Dotink\Flourish\File',
			'Text'      => 'Dotink\Flourish\Text',
			'Time'      => 'Dotink\Flourish\Time',
			'Timestamp' => 'Dotink\Flourish\Timestamp',
			'URL'       => 'Dotink\Flourish\URL',
			'UTF8'      => 'Dotink\Flourish\UTF8'
		]

	]);
}
