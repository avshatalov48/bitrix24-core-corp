<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/salary-vacation-menu.bundle.css',
	'js' => 'dist/salary-vacation-menu.bundle.js',
	'rel' => [
		'main.core',
		'main.core.cache',
		'main.popup',
	],
	'skip_core' => false,
];
