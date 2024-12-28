<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/registry.bundle.css',
	'js' => 'dist/registry.bundle.js',
	'rel' => [
		'main.core',
		'im.public',
		'im.v2.const',
		'im.v2.lib.layout',
		'im.v2.lib.logger',
		'im.v2.application.core',
	],
	'skip_core' => false,
];
