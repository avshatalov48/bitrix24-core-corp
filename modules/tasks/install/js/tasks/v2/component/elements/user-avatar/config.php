<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/user-avatar.bundle.css',
	'js' => 'dist/user-avatar.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.avatar',
	],
	'skip_core' => true,
];
