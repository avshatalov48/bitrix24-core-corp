<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/company-connect-page.bundle.css',
	'js' => 'dist/company-connect-page.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
];
