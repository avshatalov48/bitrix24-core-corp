<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/disk.onlyoffice-item.bundle.js',
	'rel' => [
		'disk',
		'ui.viewer',
		'main.polyfill.core',
	],
	'skip_core' => false,
];