<?php


namespace Bitrix\Disk\Document;

use Bitrix\Disk\Analytics\DiskAnalytics;
use Bitrix\Disk\Analytics\Enum\DocumentHandlerType;
use Bitrix\Disk\Controller\Integration\Flipchart;
use Bitrix\Disk\Document\Flipchart\BoardService;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Disk\UrlManager;
use Bitrix\Disk\User;
use Bitrix\Disk\Version;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\Response;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class LocalDocumentController extends Internals\Controller
{
	const CODE = 'l';

	const ERROR_BAD_RIGHTS                              = 'DISK_LOCAL_DOC_CON_22002';
	const ERROR_COULD_NOT_FIND_FILE                     = 'DISK_LOCAL_DOC_CON_22005';
	const ERROR_COULD_NOT_FIND_VERSION                  = 'DISK_LOCAL_DOC_CON_22006';
	const ERROR_COULD_NOT_SAVE_FILE                     = 'DISK_LOCAL_DOC_CON_22008';
	const ERROR_COULD_NOT_ADD_VERSION                   = 'DISK_LOCAL_DOC_CON_22009';
	const ERROR_COULD_NOT_FIND_STORAGE                  = 'DISK_LOCAL_DOC_CON_22012';
	const ERROR_COULD_NOT_FIND_FOLDER_FOR_CREATED_FILES = 'DISK_LOCAL_DOC_CON_22013';
	const ERROR_COULD_NOT_CREATE_FILE                   = 'DISK_LOCAL_DOC_CON_22014';
	const ERROR_COULD_NOT_FIND_FOLDER                   = 'DISK_LOCAL_DOC_CON_22015';

	const STATUS_NOT_FOUND = 'not_found';

	/** @var  File */
	protected $file;
	/** @var int */
	protected $fileId;
	/** @var  Version */
	protected $version;
	/** @var int */
	protected $versionId;

	public static function isLocalService($serviceName)
	{
		return mb_strtolower($serviceName) === self::CODE;
	}

	public static function getCode()
	{
		return self::CODE;
	}

	public static function getName()
	{
		return Loc::getMessage('DISK_LOCAL_DOC_CONTROLLER_NAME_3_WORK_WITH');
	}

	protected function listActions()
	{
		return array(
			'publishBlank' => array(
				'method' => array('POST', 'GET'),
				'name' => 'publishBlank',
				'check_csrf_token' => false,
			),
			'saveBlank' => array(
				'method' => array('POST'),
				'name' => 'commit',
			),
			'commit' => array(
				'method' => array('POST'),
				'name' => 'commit',
			),
			'show' => 'download',
			'publish' => 'download',
			'download',
		);
	}

	protected function isActionWithExistsFile()
	{
		return mb_strtolower($this->realActionName) != 'publishblank';
	}

	protected function prepareParams()
	{
		if(!$this->isActionWithExistsFile())
		{
			return true;
		}

		if(!$this->checkRequiredInputParams($_REQUEST, array('objectId')))
		{
			return false;
		}
		$this->fileId = (int)$_REQUEST['objectId'];
		if(!empty($_REQUEST['versionId']))
		{
			$this->versionId = (int)$_REQUEST['versionId'];
		}

		return true;
	}

	protected function processBeforeAction($actionName)
	{
		if($this->isActionWithExistsFile())
		{
			$this->initializeData();
			$this->checkReadPermissions();
		}

		return true;
	}

	protected function checkReadPermissions()
	{
		$securityContext = $this->file->getStorage()->getCurrentUserSecurityContext();
		if(!$this->file->canRead($securityContext))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_LOCAL_DOC_CONTROLLER_ERROR_BAD_RIGHTS'), self::ERROR_BAD_RIGHTS)));
			$this->sendJsonErrorResponse();
		}
	}

	protected function checkUpdatePermissions()
	{
		$securityContext = $this->file->getStorage()->getCurrentUserSecurityContext();
		if(!$this->file->canUpdate($securityContext))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_LOCAL_DOC_CONTROLLER_ERROR_BAD_RIGHTS'), self::ERROR_BAD_RIGHTS)));
			$this->sendJsonErrorResponse();
		}
	}

	/**
	 * @return boolean
	 */
	public function isSpecificVersion()
	{
		return (bool)$this->versionId;
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
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_LOCAL_DOC_CONTROLLER_ERROR_COULD_NOT_FIND_FILE'), self::ERROR_COULD_NOT_FIND_FILE)));
			$this->sendJsonResponse(array('status' => self::STATUS_NOT_FOUND));
		}
	}

	protected function initializeVersion($versionId)
	{
		$this->version = Version::loadById($versionId, array('OBJECT.STORAGE'));
		if(!$this->version)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_LOCAL_DOC_CONTROLLER_ERROR_COULD_NOT_FIND_FILE'), self::ERROR_COULD_NOT_FIND_FILE)));
			$this->sendJsonResponse(array('status' => self::STATUS_NOT_FOUND));
		}
		$this->file = $this->version->getObject();
		if(!$this->file)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_LOCAL_DOC_CONTROLLER_ERROR_COULD_NOT_FIND_VERSION'), self::ERROR_COULD_NOT_FIND_VERSION)));
			$this->sendJsonResponse(array('status' => self::STATUS_NOT_FOUND));
		}
	}

	protected function processActionDownload()
	{
		if ($this->isSpecificVersion())
		{
			$response = Response\BFile::createByFileId($this->version->getFileId(), $this->version->getName());
		}
		else
		{
			$response = Response\BFile::createByFileId($this->file->getFileId(), $this->file->getName());
		}

		Application::getInstance()->end(0, $response);
	}

	protected function processActionCommit()
	{
		$this->checkRequiredFilesParams(array('file'));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$this->checkUpdatePermissions();

		//todo fix Cherezov. Ban encoding 1251
		$fileArray = $this->request->getFile('file');
		$fileArray['name'] = $this->file->getName();
		$userId = $this->getUser()->getId();
		if(!$this->file->uploadVersion($fileArray, $userId))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_LOCAL_DOC_CONTROLLER_ERROR_COULD_NOT_ADD_VERSION'), self::ERROR_COULD_NOT_ADD_VERSION)));
			$this->sendJsonErrorResponse();
		}

		Driver::getInstance()->sendEvent($userId, 'live', array(
			'objectId' => $this->file->getId(),
			'action' => 'commit',
		));

		$this->sendJsonSuccessResponse();
	}

	protected function processActionPublishBlank($type)
	{
		$fileData = null;
		if($type !== 'board')
		{
			$fileData = new BlankFileData($type);
		}

		if($this->request->getPost('targetFolderId'))
		{
			$folder = Folder::loadById((int)$this->request->getPost('targetFolderId'), array('STORAGE'));
			if(!$folder)
			{
				$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_LOCAL_DOC_CONTROLLER_ERROR_COULD_NOT_FIND_FOLDER'), self::ERROR_COULD_NOT_FIND_FOLDER)));
				$this->sendJsonErrorResponse();
			}
		}
		else
		{
			$userStorage = Driver::getInstance()->getStorageByUserId($this->getUser()->getId());
			if(!$userStorage)
			{
				$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_LOCAL_DOC_CONTROLLER_ERROR_COULD_NOT_FIND_STORAGE'), self::ERROR_COULD_NOT_FIND_STORAGE)));
				$this->sendJsonErrorResponse();
			}
			$folder = $userStorage->getFolderForCreatedFiles();
		}


		if(!$folder)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_LOCAL_DOC_CONTROLLER_ERROR_COULD_NOT_FIND_FOLDER_FOR_CREATED_FILES'), self::ERROR_COULD_NOT_FIND_FOLDER_FOR_CREATED_FILES)));
			$this->sendJsonErrorResponse();
		}
		$storage = $folder->getStorage();
		if(!$folder->canAdd($storage->getCurrentUserSecurityContext()))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_LOCAL_DOC_CONTROLLER_ERROR_BAD_RIGHTS'), self::ERROR_BAD_RIGHTS)));
			$this->sendJsonErrorResponse();
		}

		if ($type === 'board')
		{
			$createBoardResult = BoardService::createNewDocument(User::loadById($this->getUser()->getId()), $folder);
			$newFile = null;
			if ($createBoardResult->isSuccess())
			{
				$newFile = $createBoardResult->getData()['file'];
			}
			else
			{
				$this->errorCollection->add(
					[
						new Error(
							Loc::getMessage('DISK_LOCAL_DOC_CONTROLLER_ERROR_COULD_NOT_CREATE_FILE'),
							self::ERROR_COULD_NOT_CREATE_FILE
						)
					]
				);
				$this->errorCollection->add($createBoardResult->getErrors());
				$this->sendJsonErrorResponse();
			}
		}
		elseif ($type === 'xlsx')
		{
			$newFile = $folder->uploadFile(\CFile::makeFileArray($fileData->getSrc()), [
				'NAME' => $fileData->getName(),
				'CREATED_BY' => $this->getUser()->getId(),
			], [], true);
		}
		else
		{
			$newFile = $folder->addBlankFile(array(
				'NAME' => $fileData->getName(),
				'CREATED_BY' => $this->getUser()->getId(),
				'MIME_TYPE' => $fileData->getMimeType(),
			), array(), true);
		}


		if(!$newFile)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_LOCAL_DOC_CONTROLLER_ERROR_COULD_NOT_CREATE_FILE'), self::ERROR_COULD_NOT_CREATE_FILE)));
			$this->errorCollection->add($folder->getErrors());
			$this->sendJsonErrorResponse();
		}

		Application::getInstance()->addBackgroundJob(function () use ($newFile, $type) {
			DiskAnalytics::sendCreationFileEvent($newFile, $type === 'board' ? DocumentHandlerType::Board : DocumentHandlerType::Desktop);
		});

		$openUrl = null;
		if ($type === 'board')
		{
			$openUrl = Driver::getInstance()->getUrlManager()->getUrlForViewBoard($newFile->getId());
		}

		$this->sendJsonSuccessResponse(array(
			'ufValue' => FileUserType::NEW_FILE_PREFIX . $newFile->getId(),
			'id' => $newFile->getId(),
			'object' => array(
				'id' => $newFile->getId(),
				'name' => $newFile->getName(),
				'sizeInt' => $newFile->getSize(),
				'size' => \CFile::formatSize($newFile->getSize()),
				'extension' => $newFile->getExtension(),
				'nameWithoutExtension' => getFileNameWithoutExtension($newFile->getName()),
			),
			'folderName' => $storage->getProxyType()->getTitleForCurrentUser() . ' / ' . $folder->getName(),
			'link' => Driver::getInstance()->getUrlManager()->getUrlForStartEditFile($newFile->getId(), self::CODE),
			'openUrl' => $openUrl,
		));
	}
}