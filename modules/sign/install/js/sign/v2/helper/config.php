<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/helper.bundle.css',
	'js' => 'dist/helper.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
];