<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/company-selector.bundle.css',
	'js' => 'dist/company-selector.bundle.js',
	'rel' => [
		'main.core',
		'main.core.cache',
		'main.core.events',
		'main.date',
		'main.loader',
		'main.popup',
		'sign.tour',
		'sign.v2.api',
		'sign.v2.b2e.hcm-link-company-selector',
		'sign.v2.b2e.scheme-selector',
		'sign.v2.company-editor',
		'sign.type',
		'sign.v2.helper',
		'ui.alerts',
		'ui.entity-selector',
		'ui.label',
	],
	'skip_core' => false,
];
