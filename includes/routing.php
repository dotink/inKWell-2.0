<?php namespace Dotink\Inkwell {

	$app->register('routes', 'Dotink\Inkwell\Routes', function($links) {
		return new Routes($links);
	});

	return $app->run(new Request());
}
