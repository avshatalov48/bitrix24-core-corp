<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/user-list-popup.bundle.css',
	'js' => 'dist/user-list-popup.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'call.component.elements',
	],
	'skip_core' => true,
];
