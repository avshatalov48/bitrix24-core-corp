<?php

use Bitrix\Disk\Document\Flipchart\Configuration;
use Bitrix\Disk\Document\LocalDocumentController;
use Bitrix\Disk\Integration\Bitrix24Manager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$importHandlers = [];
$handlersManager = \Bitrix\Disk\Driver::getInstance()->getDocumentHandlersManager();
foreach ($handlersManager->getHandlersForImport() as $handler)
{
	$importHandlers[$handler::getCode()] = [
		'code' => $handler::getCode(),
		'name' => $handler::getName(),
	];
}

$documentHandlers = [];
$canCreateDocuments = \Bitrix\Disk\Configuration::canCreateFileByCloud();
if ($canCreateDocuments)
{
	foreach ($handlersManager->getHandlers() as $handler)
	{
		if ($handler instanceof \Bitrix\Disk\Document\Contract\FileCreatable)
		{
			$documentHandlers[$handler::getCode()] = [
				'code' => $handler::getCode(),
				'name' => $handler::getName(),
			];
		}
	}

	$documentHandlers[LocalDocumentController::getCode()] = [
		'code' => LocalDocumentController::getCode(),
		'name' => LocalDocumentController::getName(),
	];
}

$importFeatureId = 'disk_import_cloud_files';
$isFeatureImportEnabled = Bitrix24Manager::isFeatureEnabled($importFeatureId);
$isBoardsEnabled = Configuration::isBoardsEnabled();

return [
	'js' => 'dist/disk.uploader.uf-file.bundle.js',
	'css' => 'dist/disk.uploader.uf-file.bundle.css',
	'rel' => [
		'main.core',
		'ui.design-tokens',
		'ui.uploader.vue',
		'ui.uploader.tile-widget',
		'main.popup',
		'ui.info-helper',

		// it would be better to load these extensions on demand
		'disk.document',
		'disk.viewer.actions',
	],
	'skip_core' => false,
	'settings' => [
		'canCreateDocuments' => $canCreateDocuments,
		'documentHandlers' => $documentHandlers,
		'importHandlers' => $importHandlers,
		'canUseImport' => $isFeatureImportEnabled,
		'importFeatureId' => 'disk_import_cloud_files',
		'isBoardsEnabled' => $isBoardsEnabled,
	],
];
