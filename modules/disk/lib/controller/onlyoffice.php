<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Document;
use Bitrix\Disk\Document\OnlyOffice\BlankFileData;
use Bitrix\Disk\Document\OnlyOffice\Models;
use Bitrix\Disk\Document\OnlyOffice\Models\DocumentSession;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Engine;
use Bitrix\Disk\User;
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter\ContentType;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\JsonPayload;
use Bitrix\Main\Engine\Response;
use Bitrix\Main\Engine\Router;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Error;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Web\HttpClient;

final class OnlyOffice extends Engine\Controller
{
	/**
	 * Document is being edited.
	 * @var int
	 */
	protected const STATUS_IS_BEING_EDITED = 1;
	/**
	 * Document is ready for saving.
	 * @var int
	 */
	protected const STATUS_IS_READY_FOR_SAVE = 2;
	/**
	 * Document saving error has occurred.
	 * @var int
	 */
	protected const STATUS_ERROR_WHILE_SAVING = 3;
	/**
	 * Document is closed with no changes.
	 * @var int
	 */
	protected const STATUS_CLOSE_WITHOUT_CHANGES = 4;
	/**
	 * Document is being edited, but the current document state is saved.
	 * @var int
	 */
	protected const STATUS_FORCE_SAVE = 6;
	/**
	 * Error has occurred while force saving the document.
	 * @var int
	 */
	protected const STATUS_ERROR_WHILE_FORCE_SAVING = 7;

	protected const ACTION_TYPE_DISCONNECT = 0;
	protected const ACTION_TYPE_CONNECT = 1;
	protected const ACTION_TYPE_FORCE_SAVE = 2;

	/** @var array */
	protected $documentSessions;
	private $addExtendedErrorInfo = false;

	public function getAutoWiredParameters()
	{
		return [
			new ExactParameter(
				Models\DocumentSession::class,
				'documentSession',
				function($className, $documentSessionHash) {
					return Models\DocumentSession::load([
						'=EXTERNAL_HASH' => $documentSessionHash,
					]);
				}
			),
		];
	}

	public function configureActions()
	{
		return [
			'loadDocumentEditor' => [
				'-prefilters' => [Csrf::class],
			],
			'loadCreateDocumentEditor' => [
				'-prefilters' => [Csrf::class],
			],
			'loadDocumentViewer' => [
				'-prefilters' => [Csrf::class],
			],
			'loadDocumentEditorByViewSession' => [
				'-prefilters' => [Csrf::class],
				'+prefilters' => [
					(new Document\OnlyOffice\Filters\DocumentSessionCheck())
						->enableOwnerCheck()
						->enableHashCheck(function(){
							return Context::getCurrent()->getRequest()->get('documentSessionHash');
						})
					,
				],
			],
			'renameDocument' => [
				'+prefilters' => [
					new HttpMethod([HttpMethod::METHOD_POST]),
					new ContentType([ContentType::JSON]),
					(new Document\OnlyOffice\Filters\DocumentSessionCheck())
						->enableOwnerCheck()
						->enableHashCheck(function(){
							return (new JsonPayload())->getData()['documentSessionHash'];
						})
					,
				],
			],
			'endSession' => [
				'+prefilters' => [
					new HttpMethod([HttpMethod::METHOD_POST]),
					new ContentType([ContentType::JSON]),
					(new Document\OnlyOffice\Filters\DocumentSessionCheck())
						->enableOwnerCheck()
						->enableHashCheck(function(){
							return (new JsonPayload())->getData()['documentSessionHash'];
						})
					,
				],
			],
			'continueWithNewSession' => [
				'+prefilters' => [
					new HttpMethod([HttpMethod::METHOD_POST]),
					new ContentType([ContentType::JSON]),
					(new Document\OnlyOffice\Filters\DocumentSessionCheck())
						->enableOwnerCheck()
						->enableHashCheck(function(){
							return (new JsonPayload())->getData()['documentSessionHash'];
						})
					,
				],
			],
			'handleOnlyOffice' => [
				'prefilters' => [
					new ContentType([ContentType::JSON]),
					new Document\OnlyOffice\Filters\Authorization(
						ServiceLocator::getInstance()->get('disk.onlyofficeConfiguration')->getSecretKey()
					),
				],
			],
			'download' => [
				'prefilters' => [
					new Document\OnlyOffice\Filters\Authorization(
						ServiceLocator::getInstance()->get('disk.onlyofficeConfiguration')->getSecretKey(),
						[
							'documentSessionHash',
						]
					),
				],
			],
		];
	}

	public function renameDocumentAction(Models\DocumentSession $documentSession, string $newName): ?array
	{
		if (!$documentSession->isEdit())
		{
			$this->addError(new Error('Could not rename document by view session.'));

			return null;
		}

		if (!$documentSession->canUserRename($this->getCurrentUser()))
		{
			$this->addError(new Error('Could not rename due to lack of rights.'));

			return null;
		}

		$file = $documentSession->getFile();
		if (!$file)
		{
			$this->addError(new Error('Could not find file.'));

			return null;
		}

		$newName .= ".{$file->getExtension()}";
		$newName = Disk\Ui\Text::correctFilename($newName);
		if (!$file->rename($newName, true))
		{
			$this->addErrors($this->getErrors());

			return null;
		}

		Document\OnlyOffice\OnlyOfficeHandler::renameDocument($documentSession->getExternalHash(), $newName);

		return [
			'file' => [
				'name' => $file->getName(),
			]
		];
	}

	public function continueWithNewSessionAction(Models\DocumentSession $session): ?array
	{
		$documentInfo = $session->getInfo();
		if (!$documentInfo)
		{
			$this->addError(new Error("Session {$session->getId()} doesn't have info."));

			return null;
		}

		if (!$documentInfo->isFinished())
		{
			$this->addError(new Error("Session {$session->getId()} in status {$documentInfo->getContentStatus()}"));

			return null;
		}

		$newSession = $session->cloneWithNewHash($session->getUserId());
		if (!$newSession)
		{
			$this->addErrors($session->getErrors());

			return null;
		}

		/** @see \DiskFileEditorOnlyOfficeController::getSliderContentAction */
		$link = UrlManager::getInstance()->create('getSliderContent', [
			'c' => 'bitrix:disk.file.editor-onlyoffice',
			'mode' => Router::COMPONENT_MODE_AJAX,
			'documentSessionId' => $newSession->getId(),
			'documentSessionHash' => $newSession->getExternalHash(),
		]);

		return [
			'documentSession' => [
				'id' => $newSession->getId(),
				'hash' => $newSession->getExternalHash(),
				'link' => $link,
			],
		];
	}

	public function endSessionAction(Models\DocumentSession $session): array
	{
		if ($session->isEdit())
		{
			$countActiveSessions = $session->countActiveSessions();
			if ($countActiveSessions <= 1)
			{
				if ($session->isActive() && $session->getInfo())
				{
					$session->getInfo()->setUserCount(0);
				}
				//we have to keep at least one session with hash because OnlyOffice will use
				//it when invokes callbackHandler to inform us.

				return [
					'mode' => 'edit',
					'file' => [
						'id' => $session->getObject()->getId(),
						'name' => $session->getObject()->getName(),
					],
					'activeSessions' => $countActiveSessions,
				];
			}
		}

		return [
			'mode' => 'view',
		];
	}

	public function downloadAction(Models\DocumentSession $documentSession)
	{
		$this->enableExtendedErrorInfo();

		if ($documentSession->isVersion())
		{
			return Response\BFile::createByFileId($documentSession->getVersion()->getFileId(), $documentSession->getVersion()->getName());
		}

		return Response\BFile::createByFileId($documentSession->getObject()->getFileId(), $documentSession->getObject()->getName());
	}

	public function handleOnlyOfficeAction(Models\DocumentSession $documentSession, JsonPayload $payload): ?Response\Json
	{
		$this->enableExtendedErrorInfo();

		$status = $payload->getData()['status'];

		Application::getInstance()->addBackgroundJob(function () use ($status, $documentSession){
			$this->processStatusToInfoModel($documentSession, $status);
		});

		switch ($status)
		{
			case self::STATUS_IS_BEING_EDITED:
				$this->handleDocumentIsEditing($documentSession, $payload);
				break;

			case self::STATUS_IS_READY_FOR_SAVE:
			case self::STATUS_ERROR_WHILE_SAVING:
				if ($this->saveDocument($documentSession, $payload))
				{
					$this->deactivateDocumentSessions($documentSession->getExternalHash());
					$this->commentAttachedObjects($documentSession);
				}
				break;

			case self::STATUS_CLOSE_WITHOUT_CHANGES:
				$this->handleDocumentClosedWithoutChanges($documentSession);
				break;

			case self::STATUS_FORCE_SAVE:
			case self::STATUS_ERROR_WHILE_FORCE_SAVING:
				$this->saveDocument($documentSession, $payload);
				break;
		}

		$this->processStatusToInfoModel($documentSession, $status);

		if ($this->getErrors())
		{
			return null;
		}

		return new Response\Json(['error' => 0]);
	}

	protected function processStatusToInfoModel(Models\DocumentSession $documentSession, int $status): void
	{
		$documentInfo = $documentSession->getInfo();
		if(!$documentInfo)
		{
			return;
		}

		switch ($status)
		{
			case self::STATUS_IS_BEING_EDITED:
				break;

			case self::STATUS_IS_READY_FOR_SAVE:
				$documentInfo->markAsSaved();
				break;

			case self::STATUS_ERROR_WHILE_SAVING:
				$documentInfo->markAsSavedWithError();
				break;

			case self::STATUS_CLOSE_WITHOUT_CHANGES:
				$documentInfo->markAsNoChanges();
				break;

			case self::STATUS_FORCE_SAVE:
				$documentInfo->markAsForceSaved();
				break;

			case self::STATUS_ERROR_WHILE_FORCE_SAVING:
				$documentInfo->markAsForceSavedWithError();
				break;
		}

	}

	protected function handleDocumentClosedWithoutChanges(Models\DocumentSession $documentSession): void
	{
		$this->deactivateDocumentSessions($documentSession->getExternalHash());
		$documentInfo = $documentSession->getInfo();
		if (!$documentInfo)
		{
			return;
		}

		if ($documentInfo->wasForceSaved())
		{
			$this->commentAttachedObjects($documentSession);
		}
	}

	protected function handleDocumentIsEditing(Models\DocumentSession $documentSession, JsonPayload $payload): void
	{
		$payloadData = $payload->getData();
		if ($payloadData['status'] !== self::STATUS_IS_BEING_EDITED)
		{
			return;
		}

		$countOnlineUsers = count($payloadData['users'] ?? []);
		$documentInfo = $documentSession->getInfo();
		if ($documentInfo)
		{
			$documentInfo->markAsEditing();
			$documentInfo->setUserCount($countOnlineUsers);
		}

		$userIds = [];
		$actions = $payloadData['actions'] ?? [];
		foreach ($actions as $action)
		{
			$userId = (int)($action['userid'] ?? null);
			$userIds[] = $userId;
		}

		if (!$userIds)
		{
			return;
		}

		$sessions = $this->getDocumentSessionsByKeyForUsers($documentSession->getExternalHash(), $userIds);

		$actions = $payloadData['actions'] ?? [];
		foreach ($actions as $action)
		{
			$type = $action['type'] ?? null;
			$userId = (int)($action['userid'] ?? null);
			$userSession = $sessions[$userId] ?? null;

			if (!$userSession)
			{
				continue;
			}

			if ($type === self::ACTION_TYPE_DISCONNECT)
			{
				$documentSession->setAsNonActive();
			}
			elseif ($type === self::STATUS_IS_BEING_EDITED && $documentSession->isNonActive())
			{
				$documentSession->setAsActive();
			}
		}
	}

	protected function getDocumentSessionsByKeyForUsers(string $documentSessionHash, array $userIds): array
	{
		$sessions = DocumentSession::getModelList([
			'filter' => [
				'=EXTERNAL_HASH' => $documentSessionHash,
				'@USER_ID' => $userIds,
			]
		]);

		$byUser = [];
		foreach ($sessions as $session)
		{
			$byUser[$session->getUserId()] = $session;
		}

		return $byUser;
	}

	/**
	 * @param string $documentSessionHash
	 * @return DocumentSession[]
	 */
	protected function getDocumentSessionsByKey(string $documentSessionHash): array
	{
		if ($this->documentSessions === null)
		{
			$this->documentSessions = Models\DocumentSession::getModelList([
				'filter' => [
					'=EXTERNAL_HASH' => $documentSessionHash,
				]
			]);
		}

		return $this->documentSessions;
	}

	protected function commentAttachedObjects(Models\DocumentSession $documentSession): void
	{
		$file = $documentSession->getFile();
		if (!$file)
		{
			return;
		}

		$lastVersion = $file->getLastVersion();
		if (!$lastVersion)
		{
			return;
		}

		$file->commentAttachedObjects($lastVersion);
	}

	protected function deactivateDocumentSessions(string $documentSessionHash): void
	{
		Models\DocumentSessionTable::deactivateByHash($documentSessionHash);
	}

	protected function saveDocument(Models\DocumentSession $documentSession, JsonPayload $payload): bool
	{
		$payloadData = $payload->getData();

		if (!in_array($payloadData['status'], [
			self::STATUS_IS_READY_FOR_SAVE,
			self::STATUS_FORCE_SAVE,
			self::STATUS_ERROR_WHILE_SAVING,
			self::STATUS_ERROR_WHILE_FORCE_SAVING,
		], true))
		{
			return false;
		}

		if (!$documentSession->getObject())
		{
			$this->addError(new Error('Could not find file.'));

			return false;
		}

		$downloadUri = $payloadData['url'];
		$httpClient = new HttpClient();
		$tmpFile = \CTempFile::getFileName(uniqid('_wd', true));
		checkDirPath($tmpFile);

		if ($httpClient->download($downloadUri, $tmpFile) !== false)
		{
			$tmpFileArray = \CFile::makeFileArray($tmpFile);
			$payloadData['users'] = $payloadData['users'] ?? [$documentSession->getUserId()];
			if (!is_array($payloadData['users']))
			{
				$payloadData['users'] = [$payloadData['users']];
			}

			$options = ['commentAttachedObjects' => false];
			if ($documentSession->getObject()->uploadVersion($tmpFileArray, $payloadData['users'][0], $options))
			{
				$this->sendEventToParticipants($documentSession->getExternalHash(), 'saved');

				return true;
			}
		}

		return false;
	}

	protected function sendEventToParticipants(string $documentSessionHash, string $event): void
	{
		foreach ($this->getDocumentSessionsByKey($documentSessionHash) as $session)
		{
			Driver::getInstance()->sendEvent($session->getUserId(), 'onlyoffice', [
				'object' => [
					'id' => $session->getObjectId(),
				],
				'documentSession' => [
					'hash' => $documentSessionHash,
				],
				'event' => $event,
			]);
		}
	}

	public function loadCreateDocumentEditorAction(string $typeFile, \Bitrix\Disk\Folder $targetFolder = null): ?HttpResponse
	{
		$fileData = new BlankFileData($typeFile, Context::getCurrent()->getLanguage());
		if (!$targetFolder)
		{
			$userStorage = Driver::getInstance()->getStorageByUserId($this->getCurrentUser()->getId());
			if (!$userStorage)
			{
				$this->addError(new Error('Could not load user storage.'));

				return null;
			}

			$targetFolder = $userStorage->getFolderForCreatedFiles();
		}

		if (!$targetFolder)
		{
			$this->addError(new Error('Could not find folder.'));

			return null;
		}

		$storage = $targetFolder->getStorage();
		if (!$storage || !$targetFolder->canAdd($storage->getSecurityContext($this->getCurrentUser())))
		{
			$this->addError(new Error('Bad rights. Could not add file to the folder.'));

			return null;
		}

		$newFile = $targetFolder->uploadFile(\CFile::makeFileArray($fileData->getSrc()), [
			'NAME' => $fileData->getName(),
			'CREATED_BY' => $this->getCurrentUser()->getId(),
		], [], true);

		if (!$newFile)
		{
			$this->addErrors($targetFolder->getErrors());

			return null;
		}

		return $this->loadDocumentEditorAction($newFile);
	}

	public function loadDocumentEditorByViewSessionAction(Models\DocumentSession $documentSession): ?HttpResponse
	{
		if (!$documentSession->isView())
		{
			$this->addError(new Error('Could not work with session with {edit} type.'));

			return null;
		}

		if (!$documentSession->getObject())
		{
			$this->addError(new Error("Could not find file {{$documentSession->getObjectId()}}."));
			$documentSession->delete();

			return null;
		}

		if (!$documentSession->canTransformUserToEdit($this->getCurrentUser()))
		{
			$this->addError(new Error('Could not transform document session to edit mode because lack of rights.'));

			return null;
		}

		$createdSession = $documentSession->createEditSession();
		if (!$createdSession)
		{
			$this->addErrors($documentSession->getErrors());

			return null;
		}

		if ($createdSession->getId() != $documentSession->getId())
		{
			$documentSession->delete();
		}

		$documentSession = $createdSession;

		/** @see \DiskFileEditorOnlyOfficeController::getSliderContentAction */
		$link = UrlManager::getInstance()->create('getSliderContent', [
			'c' => 'bitrix:disk.file.editor-onlyoffice',
			'mode' => Router::COMPONENT_MODE_AJAX,
			'documentSessionId' => $documentSession->getId(),
			'documentSessionHash' => $documentSession->getExternalHash(),
		]);

		return $this->redirectTo($link);
	}

	public function loadDocumentEditorAction(Disk\File $object = null, Disk\AttachedObject $attachedObject = null): ?HttpResponse
	{
		$canEdit = false;
		if ($attachedObject && !$attachedObject->canUpdate(User::resolveUserId($this->getCurrentUser())))
		{
			$attachedObject = null;
		}
		elseif ($attachedObject)
		{
			$canEdit = true;
		}

		if ($object && $attachedObject && ($object->getId() != $attachedObject->getObjectId()))
		{
			$object = null;
		}

		if ($object && !$canEdit)
		{
			$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
			if ($object->canUpdate($securityContext))
			{
				$canEdit = true;
			}
		}

		if (!$canEdit)
		{
			$this->addError(new Error('Could not find file. Empty data.'));

			return null;
		}

		return $this->loadDocumentEditor($object, null, $attachedObject, Models\DocumentSession::TYPE_EDIT);
	}

	public function loadDocumentViewerAction(Disk\File $object = null, Disk\Version $version = null, Disk\AttachedObject $attachedObject = null): ?HttpResponse
	{
		if ($object && $version && $object->getId() != $version->getObjectId())
		{
			$object = null;
		}

		if ($object === null && $version)
		{
			$object = $version->getObject();
		}

		$canRead = false;
		if ($attachedObject && !$attachedObject->canRead(User::resolveUserId($this->getCurrentUser())))
		{
			$attachedObject = null;
		}
		elseif ($attachedObject)
		{
			$canRead = true;
		}

		if ($object && $attachedObject && ($object->getId() != $attachedObject->getObjectId()))
		{
			$object = null;
		}

		if ($object && !$canRead)
		{
			$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
			if ($object->canRead($securityContext))
			{
				$canRead = true;
			}
		}

		if (!$canRead)
		{
			$this->addError(new Error('Could not view file. Bad permissions.'));

			return null;
		}

		return $this->loadDocumentEditor($object, $version, $attachedObject, Models\DocumentSession::TYPE_VIEW);
	}

	protected function loadDocumentEditor(?Disk\File $object, ?Disk\Version $version, ?Disk\AttachedObject $attachedObject, int $type): ?HttpResponse
	{
		if (!$object && !$attachedObject && !$version)
		{
			$this->addError(new Error('There is no file to preview.'));

			return null;
		}

		$file = $object;
		if ($version)
		{
			$file = $version->getObject();
		}
		elseif ($attachedObject)
		{
			$file = $attachedObject->getFile();
		}
		if (!($file instanceof \Bitrix\Disk\File))
		{
			$this->addError(new Error('Could not find the file (content).'));

			return null;
		}

		$trackedObjectManager = Driver::getInstance()->getTrackedObjectManager();
		if ($attachedObject)
		{
			$trackedObjectManager->pushAttachedObject($this->getCurrentUser()->getId(), $attachedObject, true);
		}
		else
		{
			$trackedObjectManager->pushFile($this->getCurrentUser()->getId(), $file, true);
		}

		$documentSessionContext = Models\DocumentSessionContext::tryBuildByAttachedObject($attachedObject, $file);

		$sessionManager = new Document\OnlyOffice\DocumentSessionManager();
		$sessionManager
			->setUserId($this->getCurrentUser()->getId())
			->setSessionType($type)
			->setSessionContext($documentSessionContext)
			->setFile($object)
			->setVersion($version)
			->setAttachedObject($attachedObject)
		;

		$documentSession = $sessionManager->findOrCreateSession();
		if (!$documentSession)
		{
			$this->addErrors($documentSession->getErrors());

			return null;
		}

		/** @see \DiskFileEditorOnlyOfficeController::getSliderContentAction */
		$link = UrlManager::getInstance()->create('getSliderContent', [
			'c' => 'bitrix:disk.file.editor-onlyoffice',
			'mode' => Router::COMPONENT_MODE_AJAX,
			'documentSessionId' => $documentSession->getId(),
			'documentSessionHash' => $documentSession->getExternalHash(),
		]);

		return $this->redirectTo($link);
	}

	private function enableExtendedErrorInfo(): void
	{
		$this->addExtendedErrorInfo = true;
	}

	protected function runProcessingThrowable(\Throwable $throwable)
	{
		parent::runProcessingThrowable($throwable);

		if ($this->addExtendedErrorInfo)
		{
			$httpRequest = Context::getCurrent()->getRequest();
			$this->addError(new Disk\Internals\Error\Error(
				'Detailed info',
				'onlyoffice-01',
				[
					'r' => $httpRequest->getDecodedUri(),
					'h' => $httpRequest->getHttpHost(),
				]
			));
		}
	}
}
