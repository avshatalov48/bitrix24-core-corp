<?php

namespace Bitrix\Disk\Document;

use Bitrix\Disk\Configuration;
use Bitrix\Disk\Desktop;
use Bitrix\Disk\Driver;
use Bitrix\Disk\EditSession;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Disk\Internals;
use Bitrix\Disk\Internals\EditSessionTable;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\ShowSession;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\Ui;
use Bitrix\Disk\Version;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class DocumentController extends Internals\Controller
{
	const ERROR_BAD_RIGHTS                              = 'DISK_DOC_CON_22002';
	const ERROR_UNKNOWN_HANDLER                         = 'DISK_DOC_CON_22003';
	const ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE       = 'DISK_DOC_CON_22004';
	const ERROR_COULD_NOT_FIND_FILE                     = 'DISK_DOC_CON_22005';
	const ERROR_COULD_NOT_FIND_VERSION                  = 'DISK_DOC_CON_22006';
	const ERROR_COULD_NOT_FIND_EDIT_SESSION             = 'DISK_DOC_CON_22007';
	const ERROR_COULD_NOT_SAVE_FILE                     = 'DISK_DOC_CON_22008';
	const ERROR_COULD_NOT_ADD_VERSION                   = 'DISK_DOC_CON_22009';
	const ERROR_COULD_NOT_FIND_DELETE_SESSION           = 'DISK_DOC_CON_22010';
	const ERROR_COULD_NOT_GET_FILE                      = 'DISK_DOC_CON_22011';
	const ERROR_COULD_NOT_FIND_STORAGE                  = 'DISK_DOC_CON_22012';
	const ERROR_COULD_NOT_FIND_FOLDER_FOR_CREATED_FILES = 'DISK_DOC_CON_22013';
	const ERROR_COULD_NOT_CREATE_FILE                   = 'DISK_DOC_CON_22014';

	/** @var string */
	protected $documentHandlerName;
	/** @var DocumentHandler */
	protected $documentHandler;
	/** @var DocumentHandlersManager */
	protected $documentHandlersManager;
	/** @var  File */
	protected $file;
	/** @var int */
	protected $fileId;
	/** @var  Version */
	protected $version;
	/** @var int */
	protected $versionId;

	protected function listActions()
	{
		return array(
			'start',
			'show' => array(
				'method' => array('POST'),
			),
			'publishBlank' => array(
				'method' => array('POST', 'GET'),
				'check_csrf_token' => false,
			),
			'saveBlank' => array(
				'method' => array('POST'),
			),
			'discardBlank' => array(
				'method' => array('POST'),
			),
			'rename' => array(
				'method' => array('POST'),
			),
			'publish' => array(
				'method' => array('POST', 'GET'),
				'check_csrf_token' => false,
			),
			'commit' => array(
				'method' => array('POST'),
			),
			'discard' => array(
				'method' => array('POST'),
			),
			'checkView' => array(
				'method' => array('POST'),
			),
			'getLastVersionUri' => array(
				'method' => array('POST'),
			),
		);
	}

	protected function isActionWithExistsFile()
	{
		return in_array(mb_strtolower($this->realActionName), array(
			//'start' => 'start',
			'show' => 'show',
			'publish' => 'publish',
			'commit' => 'commit',
			'rename' => 'rename',
			'discard' => 'discard',
			'getlastversionuri' => 'getlastversionuri',
		));
	}

	protected function init()
	{
		parent::init();
		$this->documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
	}

	protected function processBeforeAction($actionName)
	{
		if($actionName != 'start' && $this->request->getQuery('document_action') != 'start')
		{
			//todo hack. SocServ set backurl!
			if(mb_strpos($_SERVER['HTTP_REFERER'], 'tools/oauth') !== false)
			{
				$uri = \CHTTP::urlDeleteParams($this->request->getRequestUri(), array("sessid", "document_action"));
				$uri = \CHTTP::urlAddParams($uri, array('document_action' => 'start'));
				//restart process after authorization in social services
				LocalRedirect($uri);
			}
		}

		if($this->isActionWithExistsFile())
		{
			$this->initializeData();
			$this->checkReadPermissions();
		}

		if($actionName != 'start')
		{
			if(!$this->initializeDocumentService())
			{
				$this->sendJsonErrorResponse();
			}

			if(!$this->documentHandler->checkAccessibleTokenService())
			{
				$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE_B24', array('#NAME#' => $this->documentHandler::getName())), self::ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE)));
				$this->errorCollection->add($this->documentHandler->getErrors());
				$this->sendJsonErrorResponse();
			}

			if(!$this->documentHandler->queryAccessToken()->hasAccessToken() || $this->documentHandler->isRequiredAuthorization())
			{
				$this->sendNeedAuth();
			}
		}

		return true;
	}

	protected function redirectToAuth($backUrl = null)
	{
		if (Desktop::getDiskVersion())
		{
			$this->sendJsonAccessDeniedResponse('Need to authorize');
		}
		else
		{
			parent::redirectToAuth($backUrl);
		}
	}

	/**
	 * @return boolean
	 */
	protected function isSpecificVersion()
	{
		return (bool)$this->versionId;
	}

	protected function checkReadPermissions()
	{
		$securityContext = $this->file->getStorage()->getCurrentUserSecurityContext();
		if(!$this->file->canRead($securityContext))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_BAD_RIGHTS'), self::ERROR_BAD_RIGHTS)));
			$this->sendJsonErrorResponse();
		}
	}

	protected function checkUpdatePermissions()
	{
		$securityContext = $this->file->getStorage()->getCurrentUserSecurityContext();
		if(!$this->file->canUpdate($securityContext))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_BAD_RIGHTS'), self::ERROR_BAD_RIGHTS)));
			$this->sendJsonErrorResponse();
		}
	}

	protected function checkLockPermissions()
	{
		$securityContext = $this->file->getStorage()->getCurrentUserSecurityContext();
		
		return $this->file->canLock($securityContext);
	}
	
	protected function checkUnlockPermissions()
	{
		$securityContext = $this->file->getStorage()->getCurrentUserSecurityContext();
		
		return $this->file->canUnlock($securityContext);
	}

	protected function trackDocument(): void
	{
		$trackedObjectManager = Driver::getInstance()->getTrackedObjectManager();
		$trackedObjectManager->pushFile($this->getUser()->getId(), $this->file, true);
	}

	/**
	 * @return string
	 */
	public function getDocumentHandlerName()
	{
		return $this->documentHandlerName;
	}

	/**
	 * Sets document handler name.
	 * @param string $serviceName Document handler name.
	 * @return $this
	 */
	public function setDocumentHandlerName($serviceName)
	{
		$this->documentHandlerName = $serviceName;
		return $this;
	}

	protected function prepareParams()
	{
		if($this->isActionWithExistsFile())
		{
			if(!$this->checkRequiredInputParams($_REQUEST, array('objectId')))
			{
				return false;
			}
			$this->fileId = (int)$_REQUEST['objectId'];
			if(!empty($_REQUEST['versionId']))
			{
				$this->versionId = (int)$_REQUEST['versionId'];
			}
		}

		return true;
	}

	protected function processActionStart()
	{
		$this->checkRequiredGetParams(array(
			'primaryAction'
		));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$currentUrl = $this->getApplication()->getCurUri();
		$currentUrl = \CHTTP::urlDeleteParams($currentUrl, array('document_action'));
		$currentUrl = \CHTTP::urlAddParams($currentUrl, array('document_action' => $this->request->getQuery('primaryAction')), array('encode' => true));
		$this->renderStartPage(array(
			'action' => $this->request->getQuery('primaryAction'),
			'url' => $currentUrl,
		));
	}

	protected function renderStartPage($vars)
	{
		$this->getApplication()->restartBuffer();
		extract($vars);
		extract(array(
			'APPLICATION' => $this->getApplication(),
			'USER' => $this->getUser(),
		));
		include __DIR__ . '/../../document-startpage/startpage.php';

		$this->end();
	}

	protected function onBeforeActionShow()
	{
		if(
			Bitrix24Manager::isEnabled() &&
			!Bitrix24Manager::isAccessEnabled('disk', $this->getUser()->getId()))
		{
			$this->sendJsonResponse(array(
				'status' => $this::STATUS_RESTRICTION,
			));
		}
	}

	protected function processActionShow()
	{
		$fileData = $this->prepareFileData();

		$dataForView = $this->documentHandler->getDataForViewFile($fileData);
		if (!$dataForView)
		{
			if (
				!$this->documentHandler->queryAccessToken()->hasAccessToken() ||
				$this->documentHandler->isRequiredAuthorization()
			)
			{
				$this->sendNeedAuth();
			}
		}


		if ($this->documentHandler->getErrors())
		{
			$this->errorCollection->add($this->documentHandler->getErrors());
			$this->sendJsonErrorResponse($dataForView?: array());
		}

		if (!$dataForView)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_PLEASE_RELOAD_PAGE'));
			$this->sendJsonErrorResponse();
		}

		$dataForView['service'] = $this->getDocumentHandlerName();
		$this->sendJsonSuccessResponse($dataForView);
	}

	protected function prepareFileData()
	{
		$fileData = new FileData();
		$fileData->setFile($this->file);
		$fileData->setVersion($this->version);
		$fileData->setName($this->file->getName());
		$fileData->setMimeType(TypeFile::getMimeTypeByFilename($this->file->getName()));

		return $fileData;
	}

	protected function processActionCheckView()
	{
		$this->checkRequiredPostParams(array(
			'id'
		));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$fileData = new FileData();
		$fileData->setId($this->request->getPost('id'));

		$result = $this->documentHandler->checkViewFile($fileData);
		if($result === null)
		{
			$this->errorCollection->add($this->documentHandler->getErrors());
			$this->sendJsonErrorResponse();
		}
		$this->sendJsonSuccessResponse(array('viewed' => $result));
	}

	protected function onBeforeActionPublishBlank()
	{
		//we calculated count. We don't prevent action. It's hack, because edit action is secondary after view.
		if(Bitrix24Manager::isEnabled())
		{
			Bitrix24Manager::isAccessEnabled('disk', $this->getUser()->getId());
		}
	}

	protected function processActionPublishBlank()
	{
		$this->checkRequiredGetParams(array(
			'type'
		));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$fileData = new BlankFileData($this->request->getQuery('type'));
		$fileData = $this->documentHandler->createBlankFile($fileData);
		if(!$fileData)
		{
			if($this->documentHandler->isRequiredAuthorization())
			{
				$this->sendNeedAuth();
			}
			$this->errorCollection->add($this->documentHandler->getErrors());
			$this->sendJsonErrorResponse();
		}
		$session = $this->addCreateEditSessionByCurrentUser($fileData);
		if(!$session)
		{
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'editSessionId' => $session->getId(),
			'id' => $fileData->getId(),
			'link' => $fileData->getLinkInService(),
		));
	}

	protected function processActionSaveBlank()
	{
		$this->checkRequiredGetParams(array(
			'type'
		));
		$this->checkRequiredPostParams(array(
			'editSessionId'
		));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$currentSession = $this->getEditSessionByCurrentUser((int)$this->request->getPost('editSessionId'));
		if(!$currentSession)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_COULD_NOT_FIND_EDIT_SESSION'), self::ERROR_COULD_NOT_FIND_EDIT_SESSION)));
			$this->sendJsonErrorResponse();
		}

		$tmpFile = \CTempFile::getFileName(uniqid('_wd'));
		checkDirPath($tmpFile);

		$fileData = new BlankFileData($this->request->getQuery('type'));
		$fileData->setId($currentSession->getServiceFileId());
		$fileData->setSrc($tmpFile);

		$fileData = $this->documentHandler->downloadFile($fileData);
		if(!$fileData)
		{
			if($this->documentHandler->isRequiredAuthorization())
			{
				$this->sendNeedAuth();
			}
			$this->errorCollection->add($this->documentHandler->getErrors());
			$this->sendJsonErrorResponse();
		}

		$fileArray = \CFile::makeFileArray($tmpFile);
		$fileArray['name'] = $fileData->getName();
		$fileArray['type'] = $fileData->getMimeType();
		$fileArray['MODULE_ID'] = Driver::INTERNAL_MODULE_ID;

		$fileId = \CFile::saveFile($fileArray, Driver::INTERNAL_MODULE_ID, true, true);

		if(!$fileId)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_COULD_NOT_SAVE_FILE'), self::ERROR_COULD_NOT_SAVE_FILE)));
			$this->sendJsonErrorResponse();
		}

		$folder = null;
		if(!empty($_REQUEST['targetFolderId']))
		{
			$folder = $this->getFolderToSaveFile((int)$_REQUEST['targetFolderId']);
		}
		if(!$folder)
		{
			$userStorage = Driver::getInstance()->getStorageByUserId($this->getUser()->getId());
			if(!$userStorage)
			{
				\CFile::delete($fileId);
				$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_COULD_NOT_FIND_STORAGE'), self::ERROR_COULD_NOT_FIND_STORAGE)));
				$this->sendJsonErrorResponse();
			}
			$folder = $userStorage->getFolderForCreatedFiles();
		}
		if(!$folder)
		{
			\CFile::delete($fileId);
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_COULD_NOT_FIND_FOLDER_FOR_CREATED_FILES'), self::ERROR_COULD_NOT_FIND_FOLDER_FOR_CREATED_FILES)));
			$this->sendJsonErrorResponse();
		}
		if(!$folder->canAdd($folder->getStorage()->getCurrentUserSecurityContext()))
		{
			\CFile::delete($fileId);
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_BAD_RIGHTS'), self::ERROR_BAD_RIGHTS)));
			$this->sendJsonErrorResponse();
		}

		$newFile = $folder->addFile(array(
			'NAME' => Ui\Text::correctFilename($fileData->getName()),
			'FILE_ID' => $fileId,
			'SIZE' => $fileArray['size'],
			'CREATED_BY' => $this->getUser()->getId()
		), array(), true);

		if(!$newFile)
		{
			\CFile::delete($fileId);
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_COULD_NOT_CREATE_FILE'), self::ERROR_COULD_NOT_CREATE_FILE)));
			$this->errorCollection->add($folder->getErrors());
			$this->sendJsonErrorResponse();
		}

		$defaultHandlerForView = Driver::getInstance()->getDocumentHandlersManager()->getDefaultHandlerForView();
		if($this->documentHandler instanceof GoogleHandler && $defaultHandlerForView instanceof GoogleViewerHandler)
		{
			$fileDataNew = $this->documentHandler->repackDocument($fileData);
			if($fileDataNew)
			{
				$fileArray = \CFile::makeFileArray($fileDataNew->getSrc());
				$fileArray['name'] = $fileData->getName();
				$fileArray['type'] = $fileData->getMimeType();
				$fileArray['MODULE_ID'] = Driver::INTERNAL_MODULE_ID;

				$fileId = \CFile::saveFile($fileArray, Driver::INTERNAL_MODULE_ID, true, true);
				if($fileId && !$newFile->addVersion(array(
						'ID' => $fileId,
						'FILE_SIZE' => $fileArray['size'],
					), $this->getUser()->getId())
				)
				{
					\CFile::delete($fileId);
				}
			}
		}

		$this->deleteEditSession($currentSession);
		$this->deleteFile($currentSession, $fileData);

		$this->sendJsonSuccessResponse(array(
			'folderName' => $folder->getName(),
			'objectId' => $newFile->getId(),
			'sizeInt' => $newFile->getSize(),
			'size' => \CFile::formatSize($newFile->getSize()),
			'name' => $newFile->getName(),
			'extension' => $newFile->getExtension(),
			'nameWithoutExtension' => getFileNameWithoutExtension($newFile->getName()),
		));
	}

	private function getFolderToSaveFile($targetFolderId)
	{
		/** @var Folder $folder */
		$folder = Folder::loadById($targetFolderId, array('STORAGE'));
		if(!$folder)
		{
			return null;
		}

		return $folder;
	}

	protected function processActionRename()
	{
		$this->checkRequiredPostParams(array(
			'newName'
		));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$this->checkUpdatePermissions();
		if(!$this->file->rename($this->request->getPost('newName')))
		{
			$this->errorCollection->add($this->file->getErrors());
			$this->sendJsonErrorResponse();
		}
		$this->sendJsonSuccessResponse([
			'object' => [
				'id' => $this->file->getId(),
			],
			'objectId' => $this->file->getId(),
			'newName' => $this->file->getName(),
		]);
	}

	/**
	 * @return ShowSession
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	private function findShowSessionByCurrentUser()
	{
		$filter = array(
			'OBJECT_ID' => $this->file->getRealObjectId(),
			'USER_ID' => $this->getUser()->getId(),
			'OWNER_ID' => $this->getUser()->getId(),
			'=SERVICE' => $this->documentHandler::getCode(),
			'>=CREATE_TIME' => DateTime::createFromTimestamp($this->file->getSyncUpdateTime()->getTimestamp()),
		);

		if($this->isSpecificVersion())
		{
			$filter['VERSION_ID'] = $this->versionId;
		}

		$showSessions = ShowSession::getModelList(array(
			'filter' => $filter,
			'order' => array('CREATE_TIME' => 'DESC'),
			'limit' => 1
		));

		return array_pop($showSessions);
	}

	private function createEditSessionFromShowSession(ShowSession $showSession)
	{
		if(!$this->documentHandler instanceof Office365Handler && !$this->documentHandler instanceof GoogleHandler)
		{
			return null;
		}

		$fileData = new FileData();
		$fileData
			->setId($showSession->getServiceFileId())
			->setLinkInService($showSession->getServiceFileLink())
			->setName($this->file->getName())
		;

		/** @see \Bitrix\Disk\Document\Office365Handler::shareFileToEdit() */
		/** @see \Bitrix\Disk\Document\GoogleHandler::shareFileToEdit() */
		if(!$this->documentHandler->shareFileToEdit($fileData))
		{
			return null;
		}

		return $this->addFileEditSessionByCurrentUser($fileData);
	}

	protected function onBeforeActionPublish()
	{
		//we calculated count. We don't prevent action. It's hack, because edit action is secondary after view.
		if(Bitrix24Manager::isEnabled())
		{
			Bitrix24Manager::isAccessEnabled('disk', $this->getUser()->getId());
		}
	}

	protected function processActionPublish()
	{
		if (Configuration::isEnabledObjectLock() && $this->file->getLock())
		{
			if (!$this->file->getLock()->canUnlock($this->getUser()->getId()))
			{
				$this->errorCollection->addOne($this->file->generateUnlockErrorByAnotherUser($this->file->getLock()));

				$this->sendJsonErrorResponse();
			}
		}

		$onlineSession = $this->getOnlineEditSessionForFile();
		if($onlineSession)
		{
			$forkSession = $onlineSession;
			if($onlineSession->getOwnerId() != $this->getUser()->getId())
			{
				$forkSession = $this->forkEditSessionForCurrentUser($onlineSession);
			}
			$this->sendJsonSuccessResponse(array(
				'editSessionId' => $forkSession->getId(),
				'id' => $onlineSession->getServiceFileId(),
				'link' => $onlineSession->getServiceFileLink(),
			));
		}
		elseif($this->documentHandler instanceof Office365Handler || $this->documentHandler instanceof GoogleHandler)
		{
			//todo refactor. Make the same for other handlers not only for onedrive, office365
			$showSession = $this->findShowSessionByCurrentUser();
			if($showSession)
			{
				$session = $this->createEditSessionFromShowSession($showSession);
				if($session)
				{
					$this->sendJsonSuccessResponse(array(
						'editSessionId' => $session->getId(),
						'id' => $session->getServiceFileId(),
						'link' => $session->getServiceFileLink(),
					));
				}
			}
		}
		$this->documentHandler->getErrorContainer()->clear();

		$src = $this->getFileSrcToPublish();
		if(!$src)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_COULD_NOT_GET_FILE'), self::ERROR_COULD_NOT_GET_FILE)));
			$this->sendJsonErrorResponse();
		}

		$fileData = new FileData();
		$fileData->setName($this->file->getName());
		$fileData->setMimeType(TypeFile::getMimeTypeByFilename($this->file->getName()));
		$fileData->setSrc($src);

		if(!$fileData->getSize())
		{
			$fileData->setSize(filesize($fileData->getSrc()));
		}
		if($fileData->getSize() === 0)
		{
			$fileData = $this->documentHandler->createBlankFile($fileData);
		}
		else
		{
			$fileData = $this->documentHandler->createFile($fileData);
		}
		if(!$fileData)
		{
			if($this->documentHandler->isRequiredAuthorization())
			{
				$this->sendNeedAuth();
			}
			$this->errorCollection->add($this->documentHandler->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->trackDocument();

		//if somebody publish to google similar document
		$onlineSession = $this->getOnlineEditSessionForFile();
		if($onlineSession)
		{
			$this->documentHandler->deleteFile($fileData);
			$forkSession = $onlineSession;
			if($onlineSession->getOwnerId() != $this->getUser()->getId())
			{
				$forkSession = $this->forkEditSessionForCurrentUser($onlineSession);
			}
			$this->sendJsonSuccessResponse(array(
				'editSessionId' => $forkSession->getId(),
				'id' => $onlineSession->getServiceFileId(),
				'link' => $onlineSession->getServiceFileLink(),
			));
		}
		$session = $this->addFileEditSessionByCurrentUser($fileData);

		$this->sendJsonSuccessResponse(array(
			'editSessionId' => $session->getId(),
			'id' => $fileData->getId(),
			'link' => $fileData->getLinkInService(),
		));
	}

	/**
	 *
	 * @return null|string
	 */
	protected function getFileSrcToPublish()
	{
		if($this->isSpecificVersion())
		{
			$fileId = $this->version->getFileId();
		}
		else
		{
			$fileId = $this->file->getFileId();
		}
		$fileArray = \CFile::makeFileArray($fileId);
		if(!is_array($fileArray))
		{
			return null;
		}

		return $fileArray['tmp_name'];
	}

	/**
	 * @param EditSession $session
	 * @return DocumentHandler|null
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getDocumentHandlerForSession(EditSession $session)
	{
		if($session->getOwnerId() == $session->getUserId())
		{
			return $this->documentHandler;
		}

		$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
		$documentHandler = $documentHandlersManager->getHandlerByCode($session->getService());

		if(!$documentHandler)
		{
			$this->errorCollection->add($documentHandlersManager->getErrors());

			return null;
		}

		$documentHandler->setUserId($session->getOwnerId());
		if(!$documentHandler->queryAccessToken()->hasAccessToken())
		{
			$this->errorCollection[] = new Error('Could not get token for other user.');

			return null;
		}

		return $documentHandler;
	}

	private function downloadFile(EditSession $session, FileData $fileData)
	{
		$documentHandler = $this->getDocumentHandlerForSession($session);
		if(!$documentHandler)
		{
			return null;
		}

		return $documentHandler->downloadFile($fileData);
	}

	protected function processActionCommit()
	{
		$this->checkRequiredPostParams(array(
			'editSessionId'
		));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$this->checkUpdatePermissions();

		$currentSession = $this->getEditSessionByCurrentUser((int)$this->request->getPost('editSessionId'));
		if(!$currentSession)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_COULD_NOT_FIND_EDIT_SESSION'), self::ERROR_COULD_NOT_FIND_EDIT_SESSION)));
			$this->sendJsonErrorResponse();
		}

		$tmpFile = \CTempFile::getFileName(uniqid('_wd'));
		checkDirPath($tmpFile);

		$fileData = new FileData();
		$fileData->setId($currentSession->getServiceFileId());
		$fileData->setSrc($tmpFile);

		$newNameFileAfterConvert = null;
		if($this->documentHandler->isNeedConvertExtension($this->file->getExtension()))
		{
			$newNameFileAfterConvert = getFileNameWithoutExtension($this->file->getName()) . '.' . $this->documentHandler->getConvertExtension($this->file->getExtension());
			$fileData->setMimeType(TypeFile::getMimeTypeByFilename($newNameFileAfterConvert));
		}
		else
		{
			$fileData->setMimeType(TypeFile::getMimeTypeByFilename($this->file->getName()));
		}

		$fileData = $this->downloadFile($currentSession, $fileData);
		if(!$fileData)
		{
			if($this->documentHandler->isRequiredAuthorization())
			{
				$this->sendNeedAuth();
			}
			$this->errorCollection->add($this->documentHandler->getErrors());
			$this->sendJsonErrorResponse();
		}
		$this->deleteEditSession($currentSession);

		$oldName = $this->file->getName();
		//rename in cloud service
		$renameInCloud = $fileData->getName() && $fileData->getName() != $this->file->getName();
		if($newNameFileAfterConvert || $renameInCloud)
		{
			if($newNameFileAfterConvert && $renameInCloud)
			{
				$newNameFileAfterConvert = getFileNameWithoutExtension($fileData->getName()) . '.' . getFileExtension($newNameFileAfterConvert);
			}
			$this->file->rename($newNameFileAfterConvert);
		}

		$fileArray = \CFile::makeFileArray($tmpFile);
		$fileArray['name'] = $this->file->getName();
		$fileArray['type'] = $fileData->getMimeType();
		$fileArray['MODULE_ID'] = Driver::INTERNAL_MODULE_ID;

		$fileId = \CFile::saveFile($fileArray, Driver::INTERNAL_MODULE_ID, true, true);

		if(!$fileId)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_COULD_NOT_SAVE_FILE'), self::ERROR_COULD_NOT_SAVE_FILE)));
			$this->sendJsonErrorResponse();
		}

		$versionModel = $this->file->addVersion(array(
			'ID' => $fileId,
			'FILE_SIZE' => $fileArray['size'],
		), $this->getUser()->getId(), true);

		if(!$versionModel)
		{
			if(Configuration::isEnabledObjectLock() && $this->file->getErrorByCode(File::ERROR_EXCLUSIVE_LOCK))
			{
				$this->processWithLockedFile($fileId, $fileArray);
			}

			\CFile::delete($fileId);
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_COULD_NOT_ADD_VERSION'), self::ERROR_COULD_NOT_ADD_VERSION)));
			$this->errorCollection->add($this->file->getErrors());
			$this->sendJsonErrorResponse();
		}

		$defaultHandlerForView = Driver::getInstance()->getDocumentHandlersManager()->getDefaultHandlerForView();
		if($this->documentHandler instanceof GoogleHandler && $defaultHandlerForView instanceof GoogleViewerHandler)
		{
			$fileDataNew = $this->documentHandler->repackDocument($fileData);
			if($fileDataNew)
			{
				$fileArray = \CFile::makeFileArray($fileDataNew->getSrc());
				$fileArray['name'] = $this->file->getName();
				$fileArray['type'] = $fileData->getMimeType();
				$fileArray['MODULE_ID'] = Driver::INTERNAL_MODULE_ID;

				$fileId = \CFile::saveFile($fileArray, Driver::INTERNAL_MODULE_ID, true, true);
				if($fileId && !$this->file->addVersion(array(
						'ID' => $fileId,
						'FILE_SIZE' => $fileArray['size'],
					), $this->getUser()->getId())
				)
				{
					\CFile::delete($fileId);
				}
			}
		}

		if($this->isLastEditSessionForFile())
		{
			$this->deleteFile($currentSession, $fileData);
		}

		$this->sendJsonSuccessResponse([
			'object' => [
				'id' => $this->file->getId(),
			],
			'objectId' => $this->file->getId(),
			'newName' => $this->file->getName(),
			'oldName' => $oldName,
		]);
	}

	protected function processActionGetLastVersionUri()
	{
		$result = array(
			'src' => '',
			'iframeSrc' => '',
			'editUrl' => '',
		);
		$lastVersion = $this->file->getLastVersion();
		if($lastVersion)
		{
			$urlManager = Driver::getInstance()->getUrlManager();
			$result['editUrl'] = $urlManager->getUrlForStartEditVersion($this->file->getId(), $lastVersion->getId(), 'gdrive');
			$result['version'] = $lastVersion->getGlobalContentVersion();
			if($lastVersion->getView()->getId() && $lastVersion->getView()->getJsViewerType())
			{
				$result['src'] = $urlManager->getUrlForShowVersionViewHtml($lastVersion, array('sizeType' => 'absolute'));
				$result['iframeSrc'] = $urlManager->getUrlToShowVersionByService($this->file->getId(), $lastVersion->getId(), 'gvdrive');
			}
			else
			{
				$result['iframeSrc'] = $result['src'] = $urlManager->getUrlToShowVersionByService($this->file->getId(), $lastVersion->getId(), 'gvdrive');
			}
		}

		$this->sendJsonSuccessResponse($result);
	}

	protected function processActionDiscardBlank()
	{
		$this->processActionDiscard();
	}

	protected function processActionDiscard()
	{
		$this->checkRequiredPostParams(array(
			'editSessionId'
		));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$currentSession = $this->getEditSessionByCurrentUser((int)$this->request->getPost('editSessionId'));
		if(!$currentSession)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_COULD_NOT_FIND_EDIT_SESSION'), self::ERROR_COULD_NOT_FIND_EDIT_SESSION)));
			$this->sendJsonErrorResponse();
		}
		if(!$this->deleteEditSession($currentSession))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_COULD_NOT_FIND_DELETE_SESSION'), self::ERROR_COULD_NOT_FIND_DELETE_SESSION)));
			$this->sendJsonErrorResponse();
		}

		$fileData = new FileData();
		$fileData->setId($currentSession->getServiceFileId());

		if($currentSession->isExclusive())
		{
			$this->deleteFile($currentSession, $fileData);
		}
		else
		{
			$this->initializeFile($currentSession->getObjectId());
			if ($this->isLastEditSessionForFile())
			{
				$this->deleteFile($currentSession, $fileData);
			}
		}

		$this->sendJsonSuccessResponse();
	}

	protected function initializeData()
	{
		if($this->isSpecificVersion())
		{
			$this->initializeVersion($this->versionId);
		}
		else
		{
			$this->initializeFile($this->fileId);
		}
	}

	protected function initializeFile($fileId)
	{
		$this->file = File::loadById($fileId, array('STORAGE'));
		if(!$this->file)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_COULD_NOT_FIND_FILE'), self::ERROR_COULD_NOT_FIND_FILE)));
			$this->sendJsonErrorResponse();
		}
	}

	protected function initializeVersion($versionId)
	{
		$this->version = Version::loadById($versionId, array('OBJECT.STORAGE'));
		if(!$this->version)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_COULD_NOT_FIND_FILE'), self::ERROR_COULD_NOT_FIND_FILE)));
			$this->sendJsonErrorResponse();
		}
		$this->file = $this->version->getObject();
		if(!$this->file)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_COULD_NOT_FIND_VERSION'), self::ERROR_COULD_NOT_FIND_VERSION)));
			$this->sendJsonErrorResponse();
		}
	}

	private function isViewAction()
	{
		return $this->getAction() === 'show' || $this->getAction() === 'checkView';
	}

	protected function initializeDocumentService()
	{
		if($this->isViewAction())
		{
			$this->documentHandler = $this->documentHandlersManager->getDefaultHandlerForView();
		}
		else
		{
			$this->documentHandler = $this->documentHandlersManager->getHandlerByCode($this->getDocumentHandlerName());
		}

		if(!$this->documentHandler)
		{
			return false;
		}

		$this->setDocumentHandlerName($this->documentHandler::getCode());

		return true;
	}

	/**
	 * @param EditSession $editSession
	 * @return null|EditSession
	 */
	protected function forkEditSessionForCurrentUser(EditSession $editSession)
	{
		return EditSession::add(array(
			'OBJECT_ID' => $editSession->getObjectId(),
			'USER_ID' => $this->getUser()->getId(),
			'OWNER_ID' => $editSession->getOwnerId(),
			'IS_EXCLUSIVE' => $editSession->isExclusive(),
			'SERVICE' => $editSession->getService(),
			'SERVICE_FILE_ID' => $editSession->getServiceFileId(),
			'SERVICE_FILE_LINK' => $editSession->getServiceFileLink(),
		), $this->errorCollection);
	}

	/**
	 * @param FileData $fileData
	 * @return null|EditSession
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	protected function addFileEditSessionByCurrentUser(FileData $fileData)
	{
		$data = array(
			'OBJECT_ID' => $this->file->getRealObjectId(),
			'USER_ID' => $this->getUser()->getId(),
			'OWNER_ID' => $this->getUser()->getId(),
			'SERVICE' => $this->documentHandler::getCode(),
			'SERVICE_FILE_ID' => $fileData->getId(),
			'SERVICE_FILE_LINK' => $fileData->getLinkInService(),
		);
		if($this->isSpecificVersion())
		{
			$data['VERSION_ID'] = $this->version->getId();
			$data['IS_EXCLUSIVE'] = 1;
		}

		if (Configuration::isEnabledObjectLock() && Configuration::shouldAutoLockObjectOnEdit())
		{
			if ($this->checkLockPermissions() && !$this->file->getLock())
			{
				$this->file->lock($this->getUser()->getId());
			}
		}

		return EditSession::add($data, $this->errorCollection);
	}

	/**
	 * @param FileData $fileData
	 * @return null|EditSession
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	protected function addCreateEditSessionByCurrentUser(FileData $fileData)
	{
		return EditSession::add(array(
			'USER_ID' => $this->getUser()->getId(),
			'OWNER_ID' => $this->getUser()->getId(),
			'IS_EXCLUSIVE' => 1,
			'SERVICE' => $this->documentHandler::getCode(),
			'SERVICE_FILE_ID' => $fileData->getId(),
			'SERVICE_FILE_LINK' => $fileData->getLinkInService(),
		), $this->errorCollection);
	}

	protected function isLastEditSessionForFile()
	{
		return !(bool)$this->getOnlineEditSessionForFile();
	}

	/**
	 * @return EditSession|null
	 */
	protected function getOnlineEditSessionForFile()
	{
		if($this->isSpecificVersion())
		{
			return null;
		}
		return EditSession::load(array(
			'OBJECT_ID' => $this->file->getRealObjectId(),
			'VERSION_ID' => null,
			'IS_EXCLUSIVE' => false,
		));
	}

	protected function getEditSessionByCurrentUser($sessionId)
	{
		return EditSession::load(array(
			'ID' => $sessionId,
			'USER_ID' => $this->getUser()->getId(),
			'=SERVICE' => $this->documentHandler::getCode(),
		));
	}

	protected function deleteEditSession(EditSession $editSession)
	{
		if (Configuration::isEnabledObjectLock() && Configuration::shouldAutoLockObjectOnEdit())
		{
			if ($this->checkUnlockPermissions() && $this->file->getLock())
			{
				$lock = $this->file->getLock();
				if (($lock->getCreatedBy() == $this->getUser()->getId()) && $lock->canUnlock($this->getUser()->getId()))
				{
					$this->file->unlock($this->getUser()->getId());
				}
			}
		}

		return EditSessionTable::delete($editSession->getId())->isSuccess();
	}

	/**
	 * Delete file from cloud by owner.
	 * @param EditSession $editSession
	 * @param FileData    $fileData
	 */
	protected function deleteFile(EditSession $editSession, FileData $fileData)
	{
		$documentHandler = $this->getDocumentHandlerForSession($editSession);
		if(!$documentHandler)
		{
			return;
		}

		$documentHandler->deleteFile($fileData);
	}

	protected function sendNeedAuth(array $response = array())
	{
		$this->sendJsonResponse(array_merge(array(
			'status' => self::STATUS_NEED_AUTH,
			'authUrl' => $this->documentHandler->getUrlForAuthorizeInTokenService(),
			'authUrlOpenerMode' => $this->documentHandler->getUrlForAuthorizeInTokenService('opener'),
			'serviceName' => $this->documentHandler::getName(),
		), $response));
	}

	private function processWithLockedFile($fileId, $fileArray)
	{
		$userStorage = Driver::getInstance()->getStorageByUserId($this->getUser()->getId());
		$folderForSavedFiles = $userStorage->getFolderForSavedFiles();
		if(!$folderForSavedFiles)
		{
			\CFile::delete($fileId);
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_COULD_NOT_SAVE_FILE'), self::ERROR_COULD_NOT_SAVE_FILE);
			$this->errorCollection->add($userStorage->getErrors());

			$this->sendJsonErrorResponse();
		}

		$forkedFile = $folderForSavedFiles->addFile(array(
			'NAME' => $this->file->getName(),
			'FILE_ID' => $fileId,
			'SIZE' => $fileArray['size'],
			'CREATED_BY' => $this->getUser()->getId(),
		), array(), true);
		if(!$forkedFile)
		{
			\CFile::delete($fileId);
			$this->errorCollection[] = new Error(Loc::getMessage('DISK_DOC_CONTROLLER_ERROR_COULD_NOT_SAVE_FILE'), self::ERROR_COULD_NOT_SAVE_FILE);
			$this->errorCollection->add($folderForSavedFiles->getErrors());

			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'originalIsLocked' => true,
			'forkedObject' => array(
				'id' => $forkedFile->getId(),
				'name' => $forkedFile->getName(),
			)
		));
	}
}
