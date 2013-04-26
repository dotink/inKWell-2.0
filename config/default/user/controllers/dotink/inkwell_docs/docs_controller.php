<?php namespace Dotink\Inkwell
{
	return Config::create(['Controller', '@routing', '@redirects'], [

		'doc_root' => 'external/docs',

		'@routing' => [
			'actions' => [
				'/[*:path]' => 'Dotink\InkwellDocs\DocsController::show'
			]
		],

		'@redirects' => [
			'301' => [
				'/docs' => '/#docs'
			]
		]
	]);
}