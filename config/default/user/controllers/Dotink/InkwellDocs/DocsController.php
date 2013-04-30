<?php namespace Dotink\Inkwell
{
	return Config::create(['Controller', '@routing', '@redirects'], [

		'doc_root' => 'external/docs',

		'@routing' => [
			'actions' => [
				'/[*:path]' => 'Dotink\InkwellDocs\DocsController::show'
			],
			'handlers' => [
				HTTP\NOT_FOUND => 'Dotink\InkwellDocs\DocsController::missing'
			]
		],

		'@redirects' => [
			'301' => [
				'/docs' => '/#docs'
			]
		]
	]);
}