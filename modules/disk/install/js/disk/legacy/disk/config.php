<?php

use Bitrix\Disk\Document\OnlyOffice\Bitrix24Scenario;
use Bitrix\Disk\Document\OnlyOffice\ExporterBitrix24Scenario;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => '/bitrix/js/disk/c_disk.js',
	'css' => '/bitrix/js/disk/css/disk.css',
	'lang' => BX_ROOT . '/modules/disk/lang/' . LANGUAGE_ID . '/js_disk.php',
	'rel' => ['core', 'popup', 'ajax', 'ui.notification', 'ui.design-tokens', 'ui.fonts.opensans'],
	'oninit' => function() {
		if (\Bitrix\Main\Loader::includeModule('disk'))
		{
			$bitrix24Scenario = new Bitrix24Scenario();
			$exporterBitrix24Scenario = new ExporterBitrix24Scenario($bitrix24Scenario);
			$onlyOfficeEnabled = \Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler::isEnabled();

			$messages = [
				'disk_restriction' => false,
				'disk_onlyoffice_available' => $onlyOfficeEnabled,
				'disk_revision_api' => (int)\Bitrix\Disk\Configuration::getRevisionApi(),
				'disk_document_service' => (string)\Bitrix\Disk\UserConfiguration::getDocumentServiceCode(),
			];

			$scenarioMessages = $onlyOfficeEnabled ? $exporterBitrix24Scenario->exportToArray() : [];

			return [
				'lang_additional' => array_merge($messages, $scenarioMessages),
				'rel' => $onlyOfficeEnabled ? 'disk.onlyoffice-promo-popup' : [],
			];
		}
	},
];
