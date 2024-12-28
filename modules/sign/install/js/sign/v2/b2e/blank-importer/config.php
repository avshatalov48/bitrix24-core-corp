<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/blank-importer.bundle.css',
	'js' => 'dist/blank-importer.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'sign.v2.api',
		'ui.uploader.core',
	],
	'skip_core' => false,
];
