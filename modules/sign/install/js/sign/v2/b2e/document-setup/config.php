<?php

use Bitrix\Main\Config\Option;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/document-setup.bundle.css',
	'js' => 'dist/document-setup.bundle.js',
	'rel' => [
		'main.popup',
		'sign.v2.api',
		'sign.v2.b2e.sign-dropdown',
		'sign.feature-storage',
		'sign.v2.b2e.document-counters',
		'sign.type',
		'sign.v2.document-setup',
		'sign.v2.helper',
		'sign.v2.sign-settings',
		'main.core',
		'main.date',
	],
	'settings' => [
		'isSenderTypeAvailable' => Option::get("sign", "is_sender_type_available", 'N') === 'Y',
	],
	'skip_core' => false,
];