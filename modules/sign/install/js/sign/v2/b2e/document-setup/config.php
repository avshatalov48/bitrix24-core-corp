<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/document-setup.bundle.css',
	'js' => 'dist/document-setup.bundle.js',
	'rel' => [
		'sign.feature-storage',
		'sign.type',
		'sign.v2.api',
		'sign.v2.b2e.document-counters',
		'sign.v2.b2e.sign-dropdown',
		'sign.v2.document-setup',
		'sign.v2.helper',
		'main.core',
		'main.core.cache',
		'main.core.events',
		'main.popup',
		'ui.buttons',
		'sign.v2.ui.notice',
	],
	'settings' => [
		'isSenderTypeAvailable' => \Bitrix\Sign\Config\Feature::instance()->isSenderTypeAvailable(),
	],
	'skip_core' => false,
];