<?php namespace Dotink\Inkwell {

	//
	// Redirect maps take the following format:
	//
	// type = [
	//      route => translation
	// ]
	//
	// The route is defined exactly as it is in the routes configuration with a valid route
	// pattern such as:
	//
	// /articles/[!:slug]
	//
	// The translation is similar, but does not require a pattern token.  All matching tokens from
	// the route will be placed in the translation at the specified points:
	//
	// /blog/articles/[slug]
	//
	// Full Example:
	//
	// 301 => [
    //     '/articles/[!:slug]' => '/blog/articles/[slug]'
	// ]
	//
	// The above would redirect /articles/my_awesome_article to /blog/articles/my_awesome_article
	//

	return Config::create('Core', [

		//
		// Permanent redirects.
		//

		301 => [

		],

		//
		// Temporary redirects (method is preserved)
		//

		307 => [

		],
	]);
}
