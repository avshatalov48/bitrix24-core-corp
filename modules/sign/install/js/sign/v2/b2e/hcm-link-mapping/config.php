<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/hcm-link-mapping.bundle.css',
	'js' => 'dist/hcm-link-mapping.bundle.js',
	'rel' => [
		'main.core.events',
		'humanresources.hcmlink.data-mapper',
		'main.core',
		'main.loader',
		'ui.entity-selector',
		'sign.v2.api',
	],
	'skip_core' => false,
];