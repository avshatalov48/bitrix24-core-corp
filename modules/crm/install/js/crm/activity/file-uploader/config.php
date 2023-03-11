<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/file-uploader.bundle.css',
	'js' => 'dist/file-uploader.bundle.js',
	'rel' => [
		'main.core',
		'ui.uploader.tile-widget',
		'ui.design-tokens',
	],
	'skip_core' => false,
];
