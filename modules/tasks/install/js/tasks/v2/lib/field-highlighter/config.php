<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/field-highlighter.bundle.css',
	'js' => 'dist/field-highlighter.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
];
