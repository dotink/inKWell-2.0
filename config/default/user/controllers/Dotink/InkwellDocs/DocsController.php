<?php namespace Dotink\Inkwell
{
	return Config::create(['Controller', '@routing', '@redirects'], [

		'doc_root' => 'external/docs',

		'@routing' => [
			'actions' => [
				'/error'    => function() {
					throw new \Exception(
						'Purposeful throwing of an uncaught exception to show Tracy output'
					);
				},

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