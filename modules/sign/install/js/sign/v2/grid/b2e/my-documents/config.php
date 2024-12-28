<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/my-documents.bundle.css',
	'js' => 'dist/my-documents.bundle.js',
	'rel' => [
		'main.core',
		'pull.client',
	],
	'skip_core' => false,
];
