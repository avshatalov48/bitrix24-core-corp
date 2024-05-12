<?php

use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loader::requireModule('disk');

return [
	'js' => 'dist/google-drive-picker.bundle.js',
	'rel' => [
		'main.core',
	],
	'settings' => [
		'scopes' => \Bitrix\Disk\Document\GoogleHandler::getDefaultScopes(),
	],
	'skip_core' => false,
];