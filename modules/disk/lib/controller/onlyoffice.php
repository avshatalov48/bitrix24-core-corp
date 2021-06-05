<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Document;
use Bitrix\Disk\Document\OnlyOffice\BlankFileData;
use Bitrix\Disk\Document\OnlyOffice\Models;
use Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Engine;
use Bitrix\Disk\User;
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
	/** @var array */
	protected $documentSessions;

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
					new Document\OnlyOffice\Filters\DocumentSessionCheck('documentSessionHash'),
				],
			],
			'endSession' => [
				'+prefilters' => [
					new HttpMethod([HttpMethod::METHOD_POST]),
					new ContentType([ContentType::JSON]),
					new Document\OnlyOffice\Filters\DocumentSessionCheck('documentSessionHash'),
				],
			],
			'handleOnlyOffice' => [
				'prefilters' => [
					new ContentType([ContentType::JSON]),
					new Document\OnlyOffice\Filters\Authorization(
						OnlyOfficeHandler::getSecretKey()
					),
				],
			],
			'download' => [
				'prefilters' => [
					new Document\OnlyOffice\Filters\Authorization(
						OnlyOfficeHandler::getSecretKey(),
						[
							'documentSessionHash',
						]
					),
				],
			],
		];
	}

	public function endSessionAction(Models\DocumentSession $session): array
	{
		if ($session->isEdit())
		{
			$countActiveSessions = Models\DocumentSessionTable::getCount([
				'=EXTERNAL_HASH' => $session->getExternalHash(),
			]);
			if ($countActiveSessions <= 1)
			{
				//we have to keep at least one session with hash because OnlyOffice will use
				//it when invokes callbackHandler to inform us.

				return [
					'mode' => 'edit',
					'file' => [
						'name' => $session->getObject()->getName(),
					],
					'activeSessions' => $countActiveSessions,
				];
			}
		}

		$session->delete();

		return [
			'mode' => 'view',
		];
	}

	public function downloadAction(Models\DocumentSession $documentSession)
	{
		if ($documentSession->isVersion())
		{
			return Response\BFile::createByFileId($documentSession->getVersion()->getFileId(), $documentSession->getVersion()->getName());
		}

		return Response\BFile::createByFileId($documentSession->getObject()->getFileId(), $documentSession->getObject()->getName());
	}

	public function handleOnlyOfficeAction(Models\DocumentSession $documentSession, JsonPayload $payload): ?Response\Json
	{
		switch ($payload->getData()['status'])
		{
			case 1:
				//document is being edited,
				break;

			case 2:
				//document is ready for saving,
				if ($this->saveDocument($documentSession, $payload))
				{
					$this->deleteDocumentSessions($documentSession->getExternalHash());
				}

				break;

			case 3:
				//document saving error has occurred,
				break;

			case 4:
				//document is closed with no changes,
				$this->deleteDocumentSessions($documentSession->getExternalHash());
				break;

			case 6:
				//document is being edited, but the current document state is saved,
				$this->saveDocument($documentSession, $payload);
				$this->deleteDocumentSessions($documentSession->getExternalHash());
				break;

			case 7:
				//error has occurred while force saving the document.
				break;
		}

		if ($this->getErrors())
		{
			return null;
		}

		return new Response\Json(['error' => 0]);
	}

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

	protected function deleteDocumentSessions(string $documentSessionHash): void
	{
		foreach ($this->getDocumentSessionsByKey($documentSessionHash) as $session)
		{
			$session->delete();
		}

		$rest = Models\DocumentSession::getModelList([
			'filter' => [
				'=EXTERNAL_HASH' => $documentSessionHash,
			]
		]);

		foreach ($rest as $session)
		{
			$session->delete();
		}
	}

	protected function saveDocument(Models\DocumentSession $documentSession, JsonPayload $payload): bool
	{
		$payloadData = $payload->getData();
		if ($payloadData['status'] !== 2 && $payloadData['status'] !== 6)
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

			if ($documentSession->getObject()->uploadVersion($tmpFileArray, $payloadData['users'][0]))
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
				'hash' => $documentSessionHash,
				'event' => $event,
			]);
		}
	}

	public function loadCreateDocumentEditorAction(string $typeFile, \Bitrix\Disk\Folder $targetFolder = null): ?HttpResponse
	{
		$fileData = new BlankFileData($typeFile);
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
			$documentSession->delete();

			return null;
		}

		$createdSession = $documentSession->createEditSession();
		if (!$createdSession)
		{
			$this->addErrors($documentSession->getErrors());
			$documentSession->delete();

			return null;
		}

		if ($createdSession->getId() != $documentSession->getId())
		{
			$documentSession->delete();
		}

		$documentSession = $createdSession;

		$link = UrlManager::getInstance()->create('getSliderContent', [
			'c' => 'bitrix:disk.file.editor-onlyoffice',
			'mode' => Router::COMPONENT_MODE_AJAX,
			'documentSessionId' => $documentSession->getId(),
		]);

		return $this->redirectTo($link);
	}

	public function loadDocumentEditorAction(Disk\File $object = null, Disk\AttachedObject $attachedObject = null): ?HttpResponse
	{
		if ($object)
		{
			$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
			if (!$object->canUpdate($securityContext))
			{
				$this->addError(new Error('Could not edit file. Bad permissions.'));

				return null;
			}
		}
		elseif ($attachedObject)
		{
			if (!$attachedObject->canUpdate(User::resolveUserId($this->getCurrentUser())))
			{
				$this->addError(new Error('Could not edit file. Bad permissions.'));

				return null;
			}
		}
		else
		{
			$this->addError(new Error('Could not find file. Empty data.'));

			return null;
		}

		return $this->loadDocumentEditor($object, null, $attachedObject, Models\DocumentSession::TYPE_EDIT);
	}

	public function loadDocumentViewerAction(Disk\File $object = null, Disk\Version $version = null, Disk\AttachedObject $attachedObject = null): ?HttpResponse
	{
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

		$documentSession = $sessionManager->findSession() ?: $sessionManager->addSession();
		if (!$documentSession)
		{
			return null;
		}

		if ($documentSession->getUserId() != $this->getCurrentUser()->getId())
		{
			$forkSession = $documentSession->forkForUser($this->getCurrentUser()->getId(), $documentSessionContext);
			if (!$forkSession)
			{
				$this->addErrors($documentSession->getErrors());

				return null;
			}

			$documentSession = $forkSession;
		}

		$link = UrlManager::getInstance()->create('getSliderContent', [
			'c' => 'bitrix:disk.file.editor-onlyoffice',
			'mode' => Router::COMPONENT_MODE_AJAX,
			'documentSessionId' => $documentSession->getId(),
		]);

		return $this->redirectTo($link);
	}
}
