<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/badge.bundle.css',
	'js' => 'dist/badge.bundle.js',
	'rel' => [
		'main.core',
		'ui.design-tokens',
	],
	'skip_core' => false,
];
