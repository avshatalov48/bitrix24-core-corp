<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/company-selector.bundle.css',
	'js' => 'dist/company-selector.bundle.js',
	'rel' => [
		'main.loader',
		'sign.v2.b2e.hcm-link-company-selector',
		'sign.v2.b2e.scheme-selector',
		'sign.v2.company-editor',
		'main.core',
		'main.core.cache',
		'main.core.events',
		'main.date',
		'main.popup',
		'sign.tour',
		'sign.type',
		'sign.v2.helper',
		'ui.alerts',
		'ui.entity-selector',
		'ui.label',
		'sign.v2.api',
	],
	'skip_core' => false,
];
