<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/data-structures.bundle.css',
	'js' => 'dist/data-structures.bundle.js',
	'rel' => [
		'main.core',
		'crm_common',
	],
	'skip_core' => false,
];
