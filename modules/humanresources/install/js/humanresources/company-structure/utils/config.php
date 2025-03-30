<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/utils.bundle.css',
	'js' => 'dist/utils.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'humanresources.company-structure.api',
	],
	'skip_core' => true,
];
