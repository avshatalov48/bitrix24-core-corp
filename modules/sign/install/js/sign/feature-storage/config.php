<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Sign\Config;


$isSendDocumentByEmployeeEnabled = Loader::includeModule('sign')
	&& Config\Feature::instance()->isSendDocumentByEmployeeEnabled()
;

return [
	'css' => 'dist/feature-storage.bundle.css',
	'js' => 'dist/feature-storage.bundle.js',
	'rel' => [
		'main.core',
	],
	'settings' => [
		'isSendDocumentByEmployeeEnabled' => $isSendDocumentByEmployeeEnabled,
	],
	'skip_core' => false,
];
