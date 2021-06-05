<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Engine;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Error;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Loader;

final class DocumentService extends Engine\Controller
{
	public function configureActions()
	{
		return [
			'goToPreview' => ['-prefilters' => [Csrf::class]],
			'goToEdit' => ['-prefilters' => [Csrf::class]],
			'goToCreate' => ['-prefilters' => [Csrf::class]],
			'love' => ['prefilters' => [
				new HttpMethod([HttpMethod::METHOD_GET]),
				new CloseSession(),
			]],
		];
	}

	public function goToPreviewAction($serviceCode, $attachedObjectId = null, $objectId = null, $versionId = null)
	{
		$driver = Driver::getInstance();
		$handlersManager = $driver->getDocumentHandlersManager();
		$documentHandler = $handlersManager->getHandlerByCode($serviceCode);
		if (!$documentHandler)
		{
			$this->addError(new Error('There is no document service by code'));
		}

		if ($documentHandler instanceof OnlyOfficeHandler)
		{
			/** @see \Bitrix\Disk\Controller\OnlyOffice::loadDocumentViewerAction() */
			return $this->forward(OnlyOffice::class, 'loadDocumentViewer', [
				'attachedObjectId' => $attachedObjectId,
				'objectId' => $objectId,
				'versionId' => $versionId,
			]);
		}
	}

	public function goToEditAction($serviceCode, $attachedObjectId = null, $objectId = null, $documentSessionId = null)
	{
		$driver = Driver::getInstance();
		$handlersManager = $driver->getDocumentHandlersManager();
		$documentHandler = $handlersManager->getHandlerByCode($serviceCode);
		if (!$documentHandler)
		{
			$this->addError(new Error('There is no document service by code'));
		}

		if ($documentHandler instanceof OnlyOfficeHandler)
		{
			if ($documentSessionId)
			{
				/** @see \Bitrix\Disk\Controller\OnlyOffice::loadDocumentEditorByViewSessionAction() */
				return $this->forward(OnlyOffice::class, 'loadDocumentEditorByViewSession', [
					'documentSessionId' => $documentSessionId,
				]);
			}

			return $this->forward(OnlyOffice::class, 'loadDocumentEditor', [
				'attachedObjectId' => $attachedObjectId,
				'objectId' => $objectId,
			]);
		}

		$urlManager = $driver->getUrlManager();
		if ($attachedObjectId)
		{
			LocalRedirect($urlManager::getUrlToStartEditUfFileByService($attachedObjectId, $documentHandler::getCode()));

		}
		else
		{
			LocalRedirect($urlManager::getUrlForStartEditFile($objectId, $documentHandler::getCode()));
		}
	}

	public function goToCreateAction($serviceCode, $typeFile, $attachedObjectId = null, $targetFolderId = null)
	{
		$driver = Driver::getInstance();
		$handlersManager = $driver->getDocumentHandlersManager();
		$documentHandler = $handlersManager->getHandlerByCode($serviceCode);
		if (!$documentHandler)
		{
			$this->addError(new Error('There is no document service by code'));
		}

		if ($documentHandler instanceof OnlyOfficeHandler)
		{
			return $this->forward(OnlyOffice::class, 'loadCreateDocumentEditor', [
				'typeFile' => $typeFile,
				'targetFolderId' => $targetFolderId,
			]);
		}

		$urlManager = $driver->getUrlManager();
		if ($attachedObjectId)
		{
			LocalRedirect($urlManager::getUrlToStartCreateUfFileByService($typeFile, $documentHandler::getCode()));

		}
		else
		{
			LocalRedirect($urlManager::getUrlForStartCreateFile($typeFile, $documentHandler::getCode()));
		}
	}

	public function getAction($serviceCode)
	{
		$driver = Driver::getInstance();
		$handlersManager = $driver->getDocumentHandlersManager();
		$documentHandler = $handlersManager->getHandlerByCode($serviceCode);
		if (!$documentHandler)
		{
			$this->addError(new Error('There is no document service by code'));
		}

		$urlManager = $driver->getUrlManager();

		return [
			'documentService' => [
				'code' => $documentHandler::getCode(),
				'name' => $documentHandler::getName(),
				'links' => [
					'create' => $urlManager::getUrlForStartCreateFile('TYPE_FILE', $documentHandler::getCode()),
					'edit' => $urlManager::getUrlForStartEditFile('FILE_ID', $documentHandler::getCode()),
					'uf' => [
						'create' => $urlManager::getUrlToStartCreateUfFileByService('TYPE_FILE', $documentHandler::getCode()),
						'edit' => $urlManager::getUrlToStartEditUfFileByService('ATTACHED_ID', $documentHandler::getCode()),
					],
				],
				'messages' => [],
			],
		];
	}

	/**
	 * It's fake love action to show user blank page on current domain. It's necessary
	 * to work with postMessage on IE11. So we use this action while running edit of documents.
	 *
	 * @return HttpResponse
	 */
	public function loveAction()
	{
		return new HttpResponse();
	}
	
	public function setStatusWorkWithLocalDocumentAction($uidRequest, $status)
	{
		if (!Loader::includeModule('pull'))
		{
			return;
		}

		Driver::getInstance()->sendEvent($this->getCurrentUser()->getId(), 'bdisk', [
			'uidRequest' => $uidRequest,
			'status' => $status,
		]);
	}
}
