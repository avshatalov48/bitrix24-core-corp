<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/hcm-link-mapping.bundle.css',
	'js' => 'dist/hcm-link-mapping.bundle.js',
	'rel' => [
		'main.core',
		'main.loader',
		'main.core.events',
		'humanresources.hcmlink.data-mapper',
	],
	'skip_core' => false,
];