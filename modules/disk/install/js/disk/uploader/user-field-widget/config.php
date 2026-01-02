<?php

use Bitrix\Disk\Document\Contract\FileCreatable;
use Bitrix\Disk\Document\Flipchart\Configuration;
use Bitrix\Disk\Document\LocalDocumentController;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die;
}

if (!Loader::includeModule('disk'))
{
	return [];
}

$importHandlers = [];
$handlersManager = Driver::getInstance()->getDocumentHandlersManager();
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
		if ($handler instanceof FileCreatable)
		{
			$documentHandlers[$handler::getCode()] = [
				'code' => $handler::getCode(),
				'name' => $handler::getName(),
				'supportsUnifiedLink' => $handler->supportsUnifiedLink(),
			];
		}
	}

	$documentHandlers[LocalDocumentController::getCode()] = [
		'code' => LocalDocumentController::getCode(),
		'name' => LocalDocumentController::getName(),
		'supportsUnifiedLink' => false,
	];
}

$importFeatureId = 'disk_import_cloud_files';
$isFeatureImportEnabled = Bitrix24Manager::isFeatureEnabled($importFeatureId);
$isBoardsEnabled = Configuration::isBoardsEnabled();

return [
	'js' => 'dist/disk.uploader.uf-file.bundle.js',
	'css' => 'dist/disk.uploader.uf-file.bundle.css',
	'rel' => [
		'ui.uploader.vue',
		'ui.uploader.tile-widget',
		'main.core.events',
		'ui.buttons',
		'ui.info-helper',
		'disk.document',
		'ui.vue3.components.rich-loc',
		'main.core',
		'ui.system.menu',
		'ui.uploader.core',
		'ui.icons.generator',
		'ui.icon-set.api.core',
		'ui.icon-set.outline',
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
