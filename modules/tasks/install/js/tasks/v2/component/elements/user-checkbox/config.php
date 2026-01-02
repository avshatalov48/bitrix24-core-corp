<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/user-checkbox.bundle.css',
	'js' => 'dist/user-checkbox.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'tasks.v2.component.elements.user-avatar',
	],
	'skip_core' => true,
];
