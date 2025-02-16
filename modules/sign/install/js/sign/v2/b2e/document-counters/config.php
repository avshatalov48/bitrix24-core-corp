<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/document-counters.bundle.css',
	'js' => 'dist/document-counters.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'ui.icon-set.api.core',
	],
	'skip_core' => false,
];
