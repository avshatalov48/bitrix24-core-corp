<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/queue.bundle.css',
	'js' => 'dist/queue.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'im.v2.application.core',
	],
	'skip_core' => true,
];
