<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Document;
use Bitrix\Disk\Document\OnlyOffice\Filters\DocumentSessionCheck;
use Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Engine;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\Response;
use Bitrix\Main\Error;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Loader;

final class DocumentService extends Engine\Controller
{
	public function configureActions()
	{
		return [
			'viewDocument' => [
				'+prefilters' => [
					(new DocumentSessionCheck())
						->enableStrictCheckRight()
					,
					new HttpMethod([HttpMethod::METHOD_GET]),
				],
				'-prefilters' => [Csrf::class],
			],
			'restoreDocumentInteraction' => [
				'+prefilters' => [
					(new DocumentSessionCheck())
						->enableStrictCheckRight()
						->enableHashCheck(function(){
							return Context::getCurrent()->getRequest()->get('documentSessionHash');
						})
					,
					new HttpMethod([HttpMethod::METHOD_GET]),
				],
				'-prefilters' => [Csrf::class],
			],
			'downloadDocument' => [
				'+prefilters' => [
					(new DocumentSessionCheck())
						->enableStrictCheckRight()
						->enableHashCheck(function(){
							return Context::getCurrent()->getRequest()->get('documentSessionHash');
						})
					,
					new HttpMethod([HttpMethod::METHOD_GET]),
					new CloseSession(),
				],
				'-prefilters' => [Csrf::class],
			],
			'goToEditOrPreview' => ['-prefilters' => [Csrf::class]],
			'goToPreview' => ['-prefilters' => [Csrf::class]],
			'goToEdit' => ['-prefilters' => [Csrf::class]],
			'goToCreate' => ['-prefilters' => [Csrf::class]],
			'love' => ['prefilters' => [
				new HttpMethod([HttpMethod::METHOD_GET]),
				new CloseSession(),
			]],
		];
	}

	public function restoreDocumentInteractionAction(Document\Models\DocumentSession $documentSession)
	{
		$currentUser = $this->getCurrentUser();
		if (!$currentUser)
		{
			$this->addError(new Error('Could not find current user.'));

			return null;
		}

		$sessionManager = new Document\OnlyOffice\DocumentSessionManager();
		$sessionManager
			->setUserId($currentUser->getId())
			->setSessionType($documentSession->getType())
			->setSessionContext($documentSession->getContext())
			->setFile($documentSession->getFile())
			->setVersion($documentSession->getVersion())
		;

		if (!$sessionManager->lock())
		{
			$this->addError(new Error('Could not getting lock for the session.'));

			return null;
		}

		$forkedSession = $sessionManager->findOrCreateSession();
		if (!$forkedSession)
		{
			$this->addErrors($forkedSession->getErrors());

			return null;
		}
		$sessionManager->unlock();

		$content = $GLOBALS['APPLICATION']->includeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'RETURN_CONTENT' => true,
				'POPUP_COMPONENT_NAME' => 'bitrix:disk.file.editor-onlyoffice',
				'POPUP_COMPONENT_TEMPLATE_NAME' => '',
				'POPUP_COMPONENT_PARAMS' => [
					'DOCUMENT_SESSION' => $forkedSession,
					'SHOW_BUTTON_OPEN_NEW_WINDOW' => false,
				],
				'PLAIN_VIEW' => true,
				'IFRAME_MODE' => true,
				'PREVENT_LOADING_WITHOUT_IFRAME' => false,
				'USE_PADDING' => false,
			]
		);

		$response = new HttpResponse();
		$response->setContent($content);

		return $response;
	}

	public function viewDocumentAction(Document\Models\DocumentSession $documentSession): HttpResponse
	{
		$currentUser = $this->getCurrentUser();
		if ($documentSession->isOutdatedByFileContent())
		{
			$forkedSession = $documentSession->cloneWithNewHash($currentUser->getId());
			/** @see \Bitrix\Disk\Controller\DocumentService::viewDocumentAction() */
			$viewUri = $this->getActionUri('viewDocument', ['documentSessionId' => $forkedSession->getId()]);

			return $this->redirectTo($viewUri);
		}

		if (!$documentSession->belongsToUser($currentUser->getId()))
		{
			//we already checked rights in filter DocumentSessionCheck and acccess to the type of document session.
			$forkedSession = $documentSession->forkForUser($currentUser->getId(), $documentSession->getContext());
			if ($forkedSession)
			{
				/** @see \Bitrix\Disk\Controller\DocumentService::viewDocumentAction() */
				$viewUri = $this->getActionUri('viewDocument', ['documentSessionId' => $forkedSession->getId()]);

				return $this->redirectTo($viewUri);
			}

			$this->addErrors($documentSession->getErrors());
			$response = new HttpResponse();
			$response->setContent(implode("\n", $this->getErrors()));

			return $response;
		}

		$content = $GLOBALS['APPLICATION']->includeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'RETURN_CONTENT' => true,
				'POPUP_COMPONENT_NAME' => 'bitrix:disk.file.editor-onlyoffice',
				'POPUP_COMPONENT_TEMPLATE_NAME' => '',
				'POPUP_COMPONENT_PARAMS' => [
					'DOCUMENT_SESSION' => $documentSession,
					'SHOW_BUTTON_OPEN_NEW_WINDOW' => false,
				],
				'PLAIN_VIEW' => true,
				'IFRAME_MODE' => true,
				'PREVENT_LOADING_WITHOUT_IFRAME' => false,
				'USE_PADDING' => false,
			]
		);

		$response = new HttpResponse();
		$response->setContent($content);

		return $response;
	}

	public function downloadDocumentAction(Document\Models\DocumentSession $documentSession): HttpResponse
	{
		$file = $documentSession->getFile();
		$response = Response\BFile::createByFileId($file->getFileId(), $file->getName());
		$response->setCacheTime(Disk\Configuration::DEFAULT_CACHE_TIME);

		return $response;
	}

	public function goToEditOrPreviewAction($serviceCode, $attachedObjectId = null, $objectId = null, $versionId = null)
	{
		$driver = Driver::getInstance();
		$handlersManager = $driver->getDocumentHandlersManager();
		$documentHandler = $handlersManager->getHandlerByCode($serviceCode);
		if (!$documentHandler)
		{
			$this->addError(new Error('There is no document service by code'));
		}

		if (!($documentHandler instanceof OnlyOfficeHandler))
		{
			$this->addError(new Error('Work only with OnlyOffice'));
		}

		if ($this->getErrors())
		{
			return null;
		}

		$canEdit = false;

		if ($versionId)
		{
			$version = Disk\Version::getById($versionId);
			if ($version)
			{
				$objectId = $version->getObjectId();
			}
		}

		if ($objectId)
		{
			$file = Disk\File::getById($objectId);
			if ($file)
			{
				$securityContext = $file->getStorage()->getSecurityContext($this->getCurrentUser());
				$canEdit = $file->canUpdate($securityContext);
			}
		}

		if ($attachedObjectId)
		{
			$attachedObject = Disk\AttachedObject::getById($attachedObjectId);
			if ($attachedObject)
			{
				$canEdit = $canEdit || $attachedObject->canUpdate($this->getCurrentUser()->getId());
			}
		}

		if ($canEdit)
		{
			return $this->goToEditAction($serviceCode, $attachedObjectId, $objectId);
		}

		return $this->goToPreviewAction($serviceCode, $attachedObjectId, $objectId, $versionId);
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

			/** @see \Bitrix\Disk\Controller\OnlyOffice::loadDocumentEditorAction() */
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
			/** @see OnlyOffice::loadCreateDocumentEditorAction() */
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
