<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/sign-settings.bundle.css',
	'js' => 'dist/sign-settings.bundle.js',
	'rel' => [
		'main.core',
		'main.core.cache',
		'sign.feature-storage',
		'sign.type',
		'sign.v2.api',
		'sign.v2.b2e.document-send',
		'sign.v2.b2e.document-setup',
		'sign.v2.b2e.parties',
		'sign.v2.b2e.user-party',
		'sign.v2.editor',
		'sign.v2.helper',
		'sign.v2.sign-settings',
		'ui.sidepanel.layout',
		'ui.uploader.core',
	],
	'skip_core' => false,
];