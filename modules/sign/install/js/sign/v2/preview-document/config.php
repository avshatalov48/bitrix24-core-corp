<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/preview-document.bundle.css',
	'js' => 'dist/preview-document.bundle.js',
	'rel' => [
		'main.core',
		'sign.v2.api',
		'sign.v2.preview',
	],
	'skip_core' => false,
];
