<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/aha-moments.bundle.css',
	'js' => 'dist/aha-moments.bundle.js',
	'rel' => [
		'main.core',
		'spotlight',
		'ui.tour',
		'tasks.v2.core',
		'tasks.v2.const',
		'tasks.v2.provider.service.option-service',
	],
	'skip_core' => false,
];
