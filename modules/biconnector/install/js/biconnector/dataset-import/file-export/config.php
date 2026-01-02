<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/file-export.bundle.css',
	'js' => 'dist/file-export.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
];
