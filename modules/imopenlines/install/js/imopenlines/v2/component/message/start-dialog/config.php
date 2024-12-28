<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/start-dialog.bundle.css',
	'js' => 'dist/start-dialog.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'im.v2.lib.parser',
		'im.v2.component.message.base',
	],
	'skip_core' => true,
];
