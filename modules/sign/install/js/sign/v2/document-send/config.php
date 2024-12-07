<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/document-send.bundle.css',
	'js' => 'dist/document-send.bundle.js',
	'rel' => [
		'sign.v2.api',
		'main.core',
	],
	'skip_core' => false,
];