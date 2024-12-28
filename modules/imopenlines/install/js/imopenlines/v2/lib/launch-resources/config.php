<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/launch-resources.bundle.css',
	'js' => 'dist/launch-resources.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'imopenlines.v2.model',
		'imopenlines.v2.provider.pull',
	],
	'skip_core' => true,
];
