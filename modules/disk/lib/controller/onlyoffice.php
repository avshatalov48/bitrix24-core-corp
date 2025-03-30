<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Document;
use Bitrix\Disk\Document\OnlyOffice\BlankFileData;
use Bitrix\Disk\Document\Models;
use Bitrix\Disk\Document\Models\DocumentSession;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Engine;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\User;
use Bitrix\Im\Call\Call;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Action;
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
use Bitrix\Main\Loader;
use Bitrix\Main\Security\Cipher;
use Bitrix\Main\Security\SecurityException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\MimeType;
use Bitrix\Ui;

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
				DocumentSession::class,
				'documentSession',
				function($className, $documentSessionHash) {
					return DocumentSession::load([
						'=EXTERNAL_HASH' => $documentSessionHash,
					]);
				}
			),
		];
	}

	protected function getDefaultPreFilters()
	{
		$defaultPreFilters = parent::getDefaultPreFilters();
		$defaultPreFilters[] = new Document\OnlyOffice\Filters\OnlyOfficeEnabled();

		return $defaultPreFilters;
	}

	public function configureActions()
	{
		return [
			'handleEndOfTrialFeature' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
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
			'recoverSessionWithBrokenFile' => [
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
					new Document\OnlyOffice\Filters\OnlyOfficeEnabled(),
					new ContentType([ContentType::JSON]),
					new Document\OnlyOffice\Filters\Authorization(
						ServiceLocator::getInstance()->get('disk.onlyofficeConfiguration')->getSecretKey()
					),
				],
			],
			'download' => [
				'prefilters' => [
					new Document\OnlyOffice\Filters\OnlyOfficeEnabled(),
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

	public function handleEndOfTrialFeatureAction(): void
	{
		Disk\UserConfiguration::resetDocumentServiceCode();

		$bitrix24Scenario = new Document\OnlyOffice\Bitrix24Scenario();
		if ($bitrix24Scenario->isTrialEnded() && !$bitrix24Scenario->canUseView())
		{
			$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
			$defaultHandlerForView = $documentHandlersManager->getDefaultHandlerForView();
			if ($defaultHandlerForView instanceof Document\OnlyOffice\OnlyOfficeHandler)
			{
				Disk\Configuration::setDefaultViewerService(Document\BitrixHandler::getCode());
			}
		}
	}

	public function handleTrialFeatureActivationAction(): void
	{
		$trialFeatureInfo = Disk\Integration\Bitrix24Manager::getTrialFeatureInfo('disk_onlyoffice_edit');
		if (!$trialFeatureInfo)
		{
			return;
		}

		['startDate' => $startDate, 'tillDate' => $tillDate] = $trialFeatureInfo;
		$startDateTime = new DateTime($startDate, \DateTime::ISO8601);
		$tillDate = new Date($tillDate, \DateTime::ISO8601);
		$now = new DateTime();
		$secondsAfterActivation = $now->getTimestamp() - $startDateTime->getTimestamp();
		if ($secondsAfterActivation > 12*3600)
		{
			return;
		}

		$value = Option::get(Driver::INTERNAL_MODULE_ID, 'posted_message_after_onlyoffice_feature_trial', 'N');
		if ($value === 'Y')
		{
			return;
		}

		$imManager = new Disk\Integration\ImManager();
		$fromUserId = $this->getCurrentUser()->getId();

		$imManager->sendMessageToGeneralChat(
			$fromUserId,
			[
				'MESSAGE' => $this->generateMessageToChat($fromUserId, $tillDate),
				'SYSTEM' => 'Y',
			]
		);

		Option::set(Driver::INTERNAL_MODULE_ID, 'posted_message_after_onlyoffice_feature_trial', 'Y');
	}

	private function generateMessageToChat(int $fromUserId, Date $tillDate): string
	{
		$userModel = User::getById($fromUserId);
		if ($userModel && $userModel->getPersonalGender() === 'F')
		{
			return Loc::getMessage('DISK_ONLYOFFICE_MSG_TO_GENERAL_CHAT_AFTER_DEMO_FEATURE_F', [
				'#USER#' => "[USER={$fromUserId}][/USER]",
				'#TRIAL_PERIOD_END_DATE#' => $tillDate->toString(),
				'#HELPDESK_LINK#' => Ui\Util::getArticleUrlByCode(13663816),
			]);
		}

		return Loc::getMessage('DISK_ONLYOFFICE_MSG_TO_GENERAL_CHAT_AFTER_DEMO_FEATURE_M', [
			'#USER#' => "[USER={$fromUserId}][/USER]",
			'#TRIAL_PERIOD_END_DATE#' => $tillDate->toString(),
			'#HELPDESK_LINK#' => Ui\Util::getArticleUrlByCode(13663816),
		]);
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

		$result = Document\OnlyOffice\OnlyOfficeHandler::renameDocument($documentSession->getExternalHash(), $newName);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return [
			'file' => [
				'name' => $file->getName(),
			]
		];
	}

	public function recoverSessionWithBrokenFileAction(Models\DocumentSession $session): ?array
	{
		$documentInfo = $session->getInfo();
		if (!$documentInfo)
		{
			$this->addError(new Error("Session {$session->getId()} doesn't have info."));

			return null;
		}

		if ($documentInfo->isFinished())
		{
			$this->addError(new Error("Session {$session->getId()} is already finished."));

			return null;
		}

		$session->setAsBroken();
		Models\DocumentSessionTable::markAsBrokenByHash($session->getExternalHash());

		$restoreDocumentInteractionUrl = UrlManager::getInstance()->createByController(new DocumentService(), 'restoreDocumentInteraction', [
			'documentSessionHash' => $session->getExternalHash(),
			'documentSessionId' => $session->getId(),
		]);

		return [
			'link' => $restoreDocumentInteractionUrl,
		];
	}

	public function continueWithNewSessionAction(Models\DocumentSession $session, bool $force = false): ?array
	{
		$documentInfo = $session->getInfo();
		if (!$documentInfo)
		{
			$this->addError(new Error("Session {$session->getId()} doesn't have info."));

			return null;
		}

		if (!$force && !$documentInfo->isFinished())
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
			$documentInfo = $session->getInfo();
			if ($countActiveSessions <= 1 && $session->isActive())
			{
				if ($documentInfo)
				{
					$documentInfo->setUserCount(0);
				}
			}

			return [
				'mode' => 'edit',
				'file' => [
					'id' => $session->getObject()->getId(),
					'name' => $session->getObject()->getName(),
				],
				'documentSessionInfo' => [
					'contentStatus' => $documentInfo? $documentInfo->getContentStatus() : null,
					'isFinished' => $documentInfo && $documentInfo->isFinished(),
				],
				'activeSessions' => $countActiveSessions,
			];
		}

		return [
			'mode' => 'view',
		];
	}

	public function downloadAction(Models\DocumentSession $documentSession): ?Response\BFile
	{
		$this->enableExtendedErrorInfo();

		$bfileId = null;
		$fileName = null;
		if ($documentSession->isVersion() && $documentSession->getVersion())
		{
			$bfileId = $documentSession->getVersion()->getFileId();
			$fileName = $documentSession->getVersion()->getName();
		}
		elseif (!$documentSession->isVersion() && $documentSession->getFile())
		{
			$bfileId = $documentSession->getFile()->getFileId();
			$fileName = $documentSession->getFile()->getName();
		}

		if (!$bfileId || !$fileName)
		{
			$this->addError(new Error('Could find file or version for this document session'));

			return null;
		}

		return Response\BFile::createByFileId($bfileId, $fileName);
	}

	public function handleOnlyOfficeAction(Models\DocumentSession $documentSession, JsonPayload $payload): ?Response\Json
	{
		$this->enableExtendedErrorInfo();

		$payloadData = $payload->getData();
		$status = $payloadData['status'];

		$this->processRestrictionLogs($status, $payloadData);

		Application::getInstance()->addBackgroundJob(function () use ($status, $documentSession){
			$this->processStatusToInfoModel($documentSession, $status);
		});
		$this->logUsageMetrics($documentSession, $payloadData);

		switch ($status)
		{
			case self::STATUS_IS_BEING_EDITED:
				$this->handleDocumentIsEditing($documentSession, $payloadData);
				break;

			case self::STATUS_IS_READY_FOR_SAVE:
			case self::STATUS_ERROR_WHILE_SAVING:
				if ($this->saveDocument($documentSession, $payloadData))
				{
					$this->deactivateDocumentSessions($documentSession->getExternalHash());
					$this->commentAttachedObjectsOnBackground($documentSession);
				}
				break;

			case self::STATUS_CLOSE_WITHOUT_CHANGES:
				$this->handleDocumentClosedWithoutChanges($documentSession);
				break;

			case self::STATUS_FORCE_SAVE:
			case self::STATUS_ERROR_WHILE_FORCE_SAVING:
				$this->saveDocument($documentSession, $payloadData);
				break;
		}

		$this->processStatusToInfoModel($documentSession, $status);
		if ($documentSession->getInfo() && $documentSession->getInfo()->wasFinallySaved())
		{
			$this->sendEventToDocumentChannel($documentSession);
		}

		if ($this->getErrors())
		{
			return null;
		}

		return new Response\Json(['error' => 0]);
	}

	protected function processRestrictionLogs(int $status, array $hookData): void
	{
		$restrictionManager = new Document\OnlyOffice\RestrictionManager();
		if ($restrictionManager->shouldUseRestriction())
		{
			$restrictionManager->processHookData($status, $hookData);
		}
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
		AddEventToStatFile(
			'disk',
			'disk_oo_doc_closed',
			$documentSession->getExternalHash(),
			ServiceLocator::getInstance()->get('disk.onlyofficeConfiguration')->getServer(),
			'without_changes',
			0
		);

		$this->deactivateDocumentSessions($documentSession->getExternalHash());
		$documentInfo = $documentSession->getInfo();
		if (!$documentInfo)
		{
			return;
		}

		if ($documentInfo->wasForceSaved())
		{
			$this->commentAttachedObjectsOnBackground($documentSession);
		}
	}

	protected function handleDocumentIsEditing(Models\DocumentSession $documentSession, array $payloadData): void
	{
		if ($payloadData['status'] !== self::STATUS_IS_BEING_EDITED)
		{
			return;
		}

		$onlineUsers = $payloadData['users'] ?? [];
		$onlineUsers = array_map('intval', $onlineUsers);

		$documentInfo = $documentSession->getInfo();
		if ($documentInfo)
		{
			$documentInfo->markAsEditing();
			$documentInfo->setUserCount(count($onlineUsers));
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
				if (!in_array($userId, $onlineUsers, true))
				{
					$userSession->setAsNonActive();
				}
			}
			elseif (($type === self::STATUS_IS_BEING_EDITED) && $userSession->isNonActive())
			{
				$userSession->setAsActive();
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
			$this->documentSessions = DocumentSession::getModelList([
				'filter' => [
					'=EXTERNAL_HASH' => $documentSessionHash,
				]
			]);
		}

		return $this->documentSessions;
	}

	protected function commentAttachedObjectsOnBackground(Models\DocumentSession $documentSession): void
	{
		Application::getInstance()->addBackgroundJob(function () use ($documentSession){
			$this->commentAttachedObjects($documentSession);
		});
	}

	protected function commentAttachedObjects(DocumentSession $documentSession): void
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

	protected function saveDocument(DocumentSession $documentSession, array $payloadData): bool
	{
		if (!in_array($payloadData['status'], [
			self::STATUS_IS_READY_FOR_SAVE,
			self::STATUS_FORCE_SAVE,
			self::STATUS_ERROR_WHILE_SAVING,
			self::STATUS_ERROR_WHILE_FORCE_SAVING,
		], true))
		{
			return false;
		}

		if (in_array($payloadData['status'], [
			self::STATUS_IS_READY_FOR_SAVE,
			self::STATUS_ERROR_WHILE_SAVING,
		], true))
		{
			AddEventToStatFile(
				'disk',
				'disk_oo_doc_closed',
				$documentSession->getExternalHash(),
				ServiceLocator::getInstance()->get('disk.onlyofficeConfiguration')->getServer(),
				'with_changes',
				0
			);
		}

		if (empty($payloadData['url']))
		{
			$this->addError(new Error("Could not find 'url' in payload. Status: {$payloadData['status']}"));

			return false;
		}

		if (!$documentSession->getObject())
		{
			$this->addError(new Error('Could not find file.'));

			return false;
		}

		$fileDownloader = new Document\OnlyOffice\FileDownloader($payloadData['url']);
		$downloadResult = $fileDownloader->download();
		if ($downloadResult->isSuccess())
		{
			$tmpFile = $downloadResult->getData()['file'];
			$tmpFileArray = \CFile::makeFileArray($tmpFile);
			if (\in_array($tmpFileArray['type'], [
				'application/encrypted',
				'application/zip',
			], true))
			{
				$fileType = $payloadData['filetype'] ?? $documentSession->getObject()->getExtension();
				$tmpFileArray['type'] = MimeType::getByFileExtension($fileType);
			}

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

	protected function sendEventToDocumentChannel(Models\DocumentSession $documentSession): void
	{
		$documentInfo = $documentSession->getInfo();
		$objectEvent = $documentSession->getObject()->makeObjectEvent(
			'onlyoffice',
			[
				'object' => [
					'id' => $documentSession->getObjectId(),
				],
				'documentSession' => [
					'hash' => $documentSession->getExternalHash(),
				],
				'documentSessionInfo' => [
					'contentStatus' => $documentInfo->getContentStatus(),
					'wasFinallySaved' => $documentInfo->wasFinallySaved(),
				],
			]
		);

		$objectEvent->sendToObjectChannel();
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

	private function logUsageMetrics(Models\DocumentSession $documentSession, array $payloadData): void
	{
		$server = ServiceLocator::getInstance()->get('disk.onlyofficeConfiguration')->getServer();
		$actions = $payloadData['actions'] ?? [];
		foreach ($actions as $action)
		{
			$type = $action['type'] ?? null;
			$userId = (int)($action['userid'] ?? null);
			if ($type === self::ACTION_TYPE_DISCONNECT)
			{
				AddEventToStatFile('disk', 'disk_oo_user_disconnect', $documentSession->getExternalHash(), $server, '', $userId);
			}
			else if ($type === self::STATUS_IS_BEING_EDITED)
			{
				AddEventToStatFile('disk', 'disk_oo_user_join', $documentSession->getExternalHash(), $server, '', $userId);
			}
		}
	}

	public function loadCreateDocumentEditorAction(string $typeFile, \Bitrix\Disk\Folder $targetFolder = null): ?HttpResponse
	{
		$createBlankDocumentScenario = new Document\OnlyOffice\CreateBlankDocumentScenario(
			$this->getCurrentUser()->getId(),
			Context::getCurrent()->getLanguage()
		);

		if ($targetFolder)
		{
			$result = $createBlankDocumentScenario->createBlank($typeFile, $targetFolder);
		}
		else
		{
			$result = $createBlankDocumentScenario->createBlankInDefaultFolder($typeFile);
		}

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		/** @var Disk\File $newFile */
		$newFile = $result->getData()['file'];

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

	public function loadDocumentEditorAction(
		Disk\File $object = null,
		Disk\AttachedObject $attachedObject = null,
		int $editorMode = Document\OnlyOffice\Editor\ConfigBuilder::VISUAL_MODE_USUAL
	): ?HttpResponse
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
			return $this->showNotFoundPageAction();
		}

		return $this->loadDocumentEditor($object, null, $attachedObject, DocumentSession::TYPE_EDIT, $editorMode);
	}

	public function loadDocumentViewerAction(
		Disk\File $object = null,
		Disk\Version $version = null,
		Disk\AttachedObject $attachedObject = null,
		int $editorMode = Document\OnlyOffice\Editor\ConfigBuilder::VISUAL_MODE_USUAL
	): ?HttpResponse
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
			return $this->showNotFoundPageAction();
		}

		return $this->loadDocumentEditor($object, $version, $attachedObject, DocumentSession::TYPE_VIEW, $editorMode);
	}

	protected function loadDocumentEditor(
		?Disk\File $object,
		?Disk\Version $version,
		?Disk\AttachedObject $attachedObject,
		int $type,
		int $editorMode = Document\OnlyOffice\Editor\ConfigBuilder::VISUAL_MODE_USUAL
	): ?HttpResponse
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

		if (!$sessionManager->lock())
		{
			$this->addError(new Error('Could not getting lock for the session.'));

			return null;
		}
		$documentSession = $sessionManager->findOrCreateSession();
		if (!$documentSession)
		{
			$this->addErrors($documentSession->getErrors());

			return null;
		}
		$sessionManager->unlock();

		/** @see \DiskFileEditorOnlyOfficeController::getSliderContentAction */
		$link = UrlManager::getInstance()->create('getSliderContent', [
			'c' => 'bitrix:disk.file.editor-onlyoffice',
			'mode' => Router::COMPONENT_MODE_AJAX,
			'documentSessionId' => $documentSession->getId(),
			'documentSessionHash' => $documentSession->getExternalHash(),
			'editorMode' => $editorMode,
		]);

		return $this->redirectTo($link);
	}

	public function showNotFoundPageAction()
	{
		/** @see \DiskFileEditorOnlyOfficeController::showNotFoundAction() */
		$link = UrlManager::getInstance()->create('showNotFound', [
			'c' => 'bitrix:disk.file.editor-onlyoffice',
			'mode' => Router::COMPONENT_MODE_AJAX,
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
