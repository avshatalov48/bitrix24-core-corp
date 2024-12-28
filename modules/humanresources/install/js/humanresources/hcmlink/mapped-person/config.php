<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/mapped-person.bundle.css',
	'js' => 'dist/mapped-person.bundle.js',
	'rel' => [
		'humanresources.hcmlink.api',
		'humanresources.hcmlink.company-config',
		'main.core',
		'ui.tour',
	],
	'skip_core' => false,
];
