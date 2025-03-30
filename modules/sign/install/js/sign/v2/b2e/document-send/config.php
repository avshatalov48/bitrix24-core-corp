<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/document-send.bundle.css',
	'js' => 'dist/document-send.bundle.js',
	'rel' => [
		'main.core.events',
		'sign.v2.sign-settings',
		'sign.v2.b2e.user-party',
		'sign.v2.b2e.reminder-selector',
		'sign.type',
		'main.core',
		'main.loader',
		'ui.entity-selector',
		'sign.v2.api',
		'sign.v2.document-summary',
		'sign.v2.lang-selector',
		'sign.v2.helper',
		'ui.progressbar',
		'sign.v2.b2e.hcm-link-party-checker',
	],
	'skip_core' => false,
];