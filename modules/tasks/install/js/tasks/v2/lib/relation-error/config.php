<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/relation-error.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'main.popup',
		'tasks.v2.core',
		'tasks.v2.const',
	],
	'skip_core' => true,
];
