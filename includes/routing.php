<?php namespace Dotink\Inkwell
{
	$app->register('routes', 'Dotink\Inkwell\Routes', function() {
		return new Routes();
	});

	return $app->run(new Request());
}
