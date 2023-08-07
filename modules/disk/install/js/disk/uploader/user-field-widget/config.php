<?php

use Bitrix\Disk\Document\LocalDocumentController;

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

return [
	'js' => 'dist/disk.uploader.uf-file.bundle.js',
	'css' => 'dist/disk.uploader.uf-file.bundle.css',
	'rel' => [
		'main.core',
		'ui.design-tokens',
		'ui.uploader.vue',
		'ui.uploader.tile-widget',
		'main.popup',

		// it would be better to load these extensions on demand
		'disk.document',
		'disk.viewer.actions',
	],
	'skip_core' => false,
	'settings' => [
		'canCreateDocuments' => $canCreateDocuments,
		'documentHandlers' => $documentHandlers,
		'importHandlers' => $importHandlers,
	],
];
