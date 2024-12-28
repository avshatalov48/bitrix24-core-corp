<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/my-documents-grid.bundle.css',
	'js' => 'dist/my-documents-grid.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
];
