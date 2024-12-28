<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/user-list.bundle.css',
	'js' => 'dist/user-list.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'call.component.elements',
		'call.component.user-list-popup',
	],
	'skip_core' => true,
];
