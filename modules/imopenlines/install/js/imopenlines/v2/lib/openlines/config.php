<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/openlines.bundle.css',
	'js' => 'dist/openlines.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'im.v2.application.core',
		'imopenlines.v2.provider.service',
	],
	'skip_core' => true,
];
