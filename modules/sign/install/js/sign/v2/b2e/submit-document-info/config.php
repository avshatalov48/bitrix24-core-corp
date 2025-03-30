<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/submit-document-info.bundle.css',
	'js' => 'dist/submit-document-info.bundle.js',
	'rel' => [
		'main.core.cache',
		'main.core.events',
		'sign.v2.api',
		'sign.type',
		'ui.forms',
		'sign.v2.b2e.sign-link',
		'main.core',
		'ui.date-picker',
		'ui.form-elements.view',
	],
	'skip_core' => false,
];