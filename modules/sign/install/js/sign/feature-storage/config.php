<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Sign\Config;


$featureStorage = null;
if (Loader::includeModule('sign'))
{
	$featureStorage = Config\Feature::instance();
}

$isSendDocumentByEmployeeEnabled = $featureStorage?->isSendDocumentByEmployeeEnabled() ?? false;;
$isMultiDocumentLoadingEnabled = $featureStorage?->isMultiDocumentLoadingEnabled() ?? false;
$isGroupSendingEnabled = $featureStorage?->isGroupSendingEnabled() ?? false;

return [
	'css' => 'dist/feature-storage.bundle.css',
	'js' => 'dist/feature-storage.bundle.js',
	'rel' => [
		'main.core',
	],
	'settings' => [
		'isSendDocumentByEmployeeEnabled' => $isSendDocumentByEmployeeEnabled,
		'isMultiDocumentLoadingEnabled' => $isMultiDocumentLoadingEnabled,
		'isGroupSendingEnabled' => $isGroupSendingEnabled,
	],
	'skip_core' => false,
];
