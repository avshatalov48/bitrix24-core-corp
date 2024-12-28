<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/message-manager.bundle.css',
	'js' => 'dist/message-manager.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'im.v2.const',
		'imopenlines.v2.const',
	],
	'skip_core' => true,
];
