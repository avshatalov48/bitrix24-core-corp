<?php

namespace Bitrix\Disk\Bitrix24Disk\Legacy;

use Bitrix\Disk\Bitrix24Disk;
use Bitrix\Disk\Bitrix24Disk\Legacy\Exceptions\OldDiskVersionException;
use Bitrix\Disk\Configuration;
use Bitrix\Disk\Desktop;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\ProxyType;
use Bitrix\Disk\Internals\Controller;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Storage;
use Bitrix\Disk\User;
use Bitrix\Disk\Bitrix24Disk\Legacy\Exceptions\AccessDeniedException;
use Bitrix\Disk\Bitrix24Disk\Legacy\Exceptions\BadStorageAfterMigrateException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\SystemException;
use CAdminException;
use CDiskQuota;
use CExtranet;
use COption;
use CSite;
use CTimeZone;
use CUtil;

Loc::loadMessages(__FILE__);

class StorageController extends Controller
{
	const VERSION                               = 801;
	const MIN_API_DISK_VERSION                  = 29;
	const MIN_API_DISK_VERSION_FOR_NEW_SNAPSHOT = 37;

	const STATUS_TOO_BIG         = 'too_big';
	const STATUS_NOT_FOUND       = 'not_found';
	const STATUS_OLD_VERSION     = 'old_version';
	const STATUS_NON_UNIQUE_NAME = 'non_unique_name';
	const STATUS_NO_SPACE        = 'no_space';
	const STATUS_UNLIMITED       = 'unlimited';
	const STATUS_LIMITED         = 'limited';
	const STATUS_CHUNK_ERROR     = 'chunk_error';
	const STATUS_ACCESS_DENIED   = 'access_denied';
	const STATUS_TRY_LATER       = 'try_later';
	const STATUS_LOCKED          = 'locked';
	const STATUS_ERROR_TOKEN_SID = 'error_token_sid';


	const EVENT_ON_AFTER_DISK_FIRST_USAGE_BY_DAY = 'OnAfterDiskFirstUsageByDay';
	const EVENT_ON_AFTER_DISK_FILE_UPDATE        = 'OnAfterDiskFileUpdate';
	const EVENT_ON_AFTER_DISK_FILE_ADD           = 'OnAfterDiskFileAdd';
	const EVENT_ON_AFTER_DISK_FILE_DELETE        = 'OnAfterDiskFileDelete';
	const EVENT_ON_AFTER_DISK_FOLDER_UPDATE      = 'OnAfterDiskFolderUpdate';
	const EVENT_ON_AFTER_DISK_FOLDER_ADD         = 'OnAfterDiskFolderAdd';
	const EVENT_ON_AFTER_DISK_FOLDER_DELETE      = 'OnAfterDiskFolderDelete';

	const COOKIE_DISK_USAGE = 'DISK_BDISK_USAGE';

	const ERROR_CREATE_FORK_FILE = 'SC_FF_22001';

	private $ignoreQuotaError = false;
	/** @var NewDiskStorage|DiskStorage */
	private $storage;

	protected function listActions()
	{
		return array(
			'initialize' => array(
				'method' => array('POST'),
				'check_csrf_token' => false,
			),
			'snapshot' => array(
				'method' => array('POST', 'GET'),
				'check_csrf_token' => true,
			),
			'download' => array(
				'method' => array('POST'),
			),
			'delete' => array(
				'method' => array('POST'),
			),
			'upload' => array(
				'method' => array('POST'),
			),
			'rollbackUpload' => array(
				'method' => array('POST'),
			),
			'update' => array(
				'method' => array('POST'),
			),
			'lock' => array(
				'method' => array('POST'),
			),
			'unlock' => array(
				'method' => array('POST'),
			),
			'directory' => array(
				'method' => array('POST'),
			),
			'getDiskSpace' => array(
				'method' => array('POST'),
				'check_csrf_token' => false,
			),
			'getDiskQuota' => array(
				'method' => array('POST'),
			),
			'getMaxPostSize' => array(
				'method' => array('POST'),
			),
			'getChunkSize' => array(
				'method' => array('POST'),
			),
			'getPublicLink' => array(
				'method' => array('POST'),
			),
		);
	}

	protected function runProcessingException(\Exception $e)
	{
		if($e instanceof AccessDeniedException)
		{
			$this->sendJsonResponse(array(
				'status' => self::STATUS_ACCESS_DENIED,
				'message' => $e->getMessage(),
			));
		}
		elseif($e instanceof BadStorageAfterMigrateException)
		{
			$this->sendJsonResponse(array(
				'status' => self::STATUS_ERROR,
				'message'=> 'Could not get Disk\\Storage. Perhaps, it is old client, which does not reconnect. ',
			), array('http_status' => 510));
		}
		elseif($e instanceof OldDiskVersionException)
		{
			$this->sendJsonResponse(array(
				'status' => self::STATUS_ERROR,
				'message'=> 'Old version of client. Need to upgrade you Bitrix24.Disk.',
			), array('http_status' => 510));
		}
		else
		{
			$this->errorCollection->addOne(new Error($e->getMessage()));
			$this->sendJsonErrorResponse();
		}
	}

	protected function runProcessingIfUserNotAuthorized()
	{
		$this->sendJsonAccessDeniedResponse('Need to authorize');
	}

	protected function isActualApiDiskVersion()
	{
		$apiDiskVersion = Desktop::getApiDiskVersion();
		if($apiDiskVersion === 0)
		{
			//this is browser.
			return true;
		}

		return $apiDiskVersion >= self::MIN_API_DISK_VERSION;
	}

	private function isDesktopReadyForNewSnapshot()
	{
		$apiDiskVersion = Desktop::getApiDiskVersion();
		if($apiDiskVersion === 0)
		{
			//this is browser.
			return true;
		}

		return $apiDiskVersion >= self::MIN_API_DISK_VERSION_FOR_NEW_SNAPSHOT;
	}

	protected function runProcessingIfInvalidCsrfToken()
	{
		$this->sendJsonResponse(array(
			'status' => self::STATUS_ERROR_TOKEN_SID,
			'token_sid' => bitrix_sessid(),
		), array('http_status' => 403));
	}

	private function getStorage()
	{
		return $this->isDesktopReadyForNewSnapshot()? new NewDiskStorage : new DiskStorage;
	}

	protected function processBeforeAction($actionName)
	{
		if(!$this->isActualApiDiskVersion())
		{
			throw new OldDiskVersionException;
		}

		$this->storage = $this->getStorage();
		$this->storeUsagePerDay();

		CTimeZone::disable();

		return true;
	}

	protected function sendJsonResponse($response, $params = null)
	{
		if(!$this->ignoreQuotaError() && $this->isQuotaError())
		{
			parent::sendJsonResponse(array(
				'status' => static::STATUS_NO_SPACE,
			));
		}

		$detail = array();
		$errors = $this->getErrors();
		if($this->storage)
		{
			$errors = array_merge($errors, $this->storage->getErrors());
		}
		$this->appendLastErrorToErrorCollection();
		//may be duplicate rows
		foreach($errors as $error)
		{
			/** @var Error $error */
			$detail[] = array(
				'message' => str_replace('\\', '/', $error->getMessage()),
				'code' => $error->getCode(),
			);
		}


		if($detail)
		{
			$response['detail'] = $detail;
		}

		header("X-Bitrix-Disk-API: " . self::VERSION);

		parent::sendJsonResponse($response, $params);
	}

	private function storeUsagePerDay()
	{
		if(!$this->request->getCookie(self::COOKIE_DISK_USAGE))
		{
			list($y, $m, $d) = explode('-', date('Y-m-d'));

			//end of current day;
			$this->getApplication()->set_cookie(self::COOKIE_DISK_USAGE, 'Y', mktime(0, 0, 0, $m, $d + 1, $y));

			$event = new Event(Driver::INTERNAL_MODULE_ID, self::EVENT_ON_AFTER_DISK_FIRST_USAGE_BY_DAY);
			$event->send();

			return true;
		}
		return false;
	}

	protected function processActionUpload()
	{
		$this->checkRequiredFilesParams(array('file'));
		$this->checkRequiredPostParams(array('name'));

		if(
			$this->compareStringVersion(
				$_SERVER['CONTENT_LENGTH'],
				(string)min(CUtil::unformat(ini_get('upload_max_filesize')), CUtil::unformat(ini_get('post_max_size')))) > 0
		)
		{
			$this->sendJsonResponse(array('status' => self::STATUS_TOO_BIG));
		}

		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		list($startRange, $endRange, $fileSize) = $this->getContentRange();

		$tmpFileManager = new Bitrix24Disk\UploadFileManager();
		$tmpFileManager
			->setToken($this->request->getPost('token'))
			->setUser($this->getUser())
			->setFileSize($fileSize)
			->setContentRange(array($startRange, $endRange))
		;
		if(!$tmpFileManager->upload($this->request->getPost('name'), $this->request->getFile('file')))
		{
			$this->errorCollection->add($tmpFileManager->getErrors());

			if($this->errorCollection->getErrorByCode(Bitrix24Disk\TmpFile::ERROR_CLOUD_APPEND_INVALID_CHUNK_SIZE))
			{
				$this->sendJsonResponse(array(
					'status' => self::STATUS_CHUNK_ERROR,
					'chunkSize' => $tmpFileManager->getChunkSize($this->request->getPost('name'), $fileSize),
				));
			}

			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'token' => $tmpFileManager->getToken(),
		));
	}

	protected function processActionSnapshot()
	{
		//disables CBitrix24Cdn::bx24ReplaceStaticDomain(). Safe memory!
		$_REQUEST['disable_cdn'] = true;

		$this->enableIgnoreQuotaError();

		$pageState = $this->getPageStateFromRequest();

		$userStorageId = $this->getUserStorageId();
		$storage = $this->getStorageObject();
		$items = $storage
			->setStorageId($userStorageId)
			->getSnapshot($this->getVersionFromRequest(), $pageState, $nextPageState)
		;

		$paidLicense = $this->isLicensePaid();
		$this->sendJsonResponse(array(
			'settings' => array(
				'lockEnabled' => Configuration::isEnabledObjectLock(),
				'lockAllowed' => $paidLicense,

				'externalLinkEnabled' => Configuration::isEnabledExternalLink(),
				'externalLinkAllowed' => true,
			),
			'quota' => $this->getDiskQuotaData(),
			'snapshot' => $items,
			'nextPageState' => $nextPageState? (string)$nextPageState : null,
			'nextPageUrl' => $this->generateNextPageUrl($nextPageState),
		));
	}

	protected function generateNextPageUrl(Bitrix24Disk\PageState $pageState = null)
	{
		$urlManager = Driver::getInstance()->getUrlManager();
		return
			$urlManager->getHostUrl() .
			'/desktop_app/storage.php?' .
			http_build_query(array(
				'action' => 'snapshot',
				'version' => $this->getVersionFromRequest(),
				'pageState' => $pageState? (string)$pageState : '',
			))
		;
	}

	protected function isLicensePaid()
	{
		if(!Loader::includeModule('bitrix24'))
		{
			return true;
		}

		return \CBitrix24::isLicensePaid();
	}

	protected function processActionDownload()
	{
		$this->checkRequiredPostParams(array('id', 'version', 'extra', 'storageExtra', 'storageId'));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$id = $this->request->getPost('id');
		$version = $this->request->getPost('version');

		$storage = $this->getStorageObject($this->request->getPost('storageExtra'), $this->request->getPost('storageId'));
		$extra = $storage->parseElementExtra($this->request->getPost('extra'));

		$file = $storage->getFile($id, $extra);
		//not found or we have new version
		if( !$file || (!isset($file['version']) || $storage->compareVersion($file['version'], $version) != 0) )
		{
			$this->sendJsonResponse(array(
				'status' => static::STATUS_NOT_FOUND,
			));
		}
		else
		{
			if(!$storage->sendFile($file))
			{
				$this->sendJsonResponse(array(
					'status' => static::STATUS_NOT_FOUND,
					'message' => 'Not found source file',
				));
			}
		}
	}

	protected function processActionDelete()
	{
		$this->enableIgnoreQuotaError();
		$this->checkRequiredPostParams(array('id', 'version', 'extra', 'storageExtra', 'storageId')); //isDirectory
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$id = $this->request->getPost('id');
		$version = $this->request->getPost('version');
		$isDirectory = $this->request->getPost('isDirectory') == 'true';

		$lastVersion = null;
		$storage = $this->getStorageObject($this->request->getPost('storageExtra'), $this->request->getPost('storageId'));
		$extra = $storage->parseElementExtra($this->request->getPost('extra'));

		$element = $isDirectory?
			$storage->getDirectory($id, $extra):
			$storage->getFile($id, $extra);

		if($element)
		{
			if($storage->compareVersion($element['version'], $version) > 0)
			{
				$element['status'] = static::STATUS_OLD_VERSION;
				$this->sendJsonResponse($element);
			}
			$lastVersion = $isDirectory?
				$storage->deleteDirectory($element):
				$storage->deleteFile($element);
		}
		else //is already removed?
		{
			$lastVersion = $storage->getVersionDelete(array(
				'id' => $id,
				'version' => $version,
				'isDirectory' => $isDirectory,
				'extra' => $extra,
			));
		}

		if((bool)$lastVersion)
		{
			$event = new Event(
				Driver::INTERNAL_MODULE_ID,
				$isDirectory? self::EVENT_ON_AFTER_DISK_FOLDER_DELETE : self::EVENT_ON_AFTER_DISK_FILE_DELETE,
				array($element['extra']['id'], $element)
			);
			$event->send();

			$this->sendJsonSuccessResponse(array('version' => $this->storage->convertToExternalVersion((string)$lastVersion)));
		}
		$this->sendJsonResponse(array('status' => static::STATUS_NOT_FOUND));
	}

	protected function processActionDirectory()
	{
		$this->checkRequiredPostParams(array('name', 'storageExtra', 'storageId'));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$folderName = $this->request->getPost('name');
		$inRoot = $this->request->getPost('inRoot') == 'true';
		$isUpdate = $this->request->getPost('update') == 'true';

		$storage = $this->getStorageObject($this->request->getPost('storageExtra'), $this->request->getPost('storageId'));

		if(!$storage->isCorrectName($folderName, $msg))
		{
			$this->sendJsonResponse(array(
				'status' => static::STATUS_DENIED,
				'message' => $msg,
			));
		}

		$parentFolderId = null;
		if(!$inRoot)
		{
			$this->checkRequiredPostParams(array('parentExtra'));
			if($this->errorCollection->hasErrors())
			{
				$this->sendJsonErrorResponse();
			}

			$parentFolderExtra = $storage->parseElementExtra($this->request->getPost('parentExtra'));
			$parentFolderId = $parentFolderExtra['id'];
			//$parentFolderVersion = $_POST['version'];
		}

		if($isUpdate)
		{
			$this->checkRequiredPostParams(array('id', 'version'));
			if($this->errorCollection->hasErrors())
			{
				$this->sendJsonErrorResponse();
			}

			$id = $this->request->getPost('id');

			$folderExtra = $storage->parseElementExtra($this->request->getPost('extra'));
			$targetFolder = $storage->getDirectory($id, $folderExtra);
			if(empty($targetFolder))
			{
				$this->errorCollection->addOne(new Error('Not found directory to update'));
				$this->sendJsonErrorResponse();
			}

			//it is the same directory todo this logic $storage->moveDirectory, but ....we have many query. Or refactor signature
			if($targetFolder['extra']['sectionId'] == $parentFolderId && $folderName == $targetFolder['name'])
			{
				$this->sendJsonSuccessResponse($targetFolder);
			}
			if($folderName != $targetFolder['name'])
			{
				$item = $storage->renameDirectory($folderName, $targetFolder['extra']['id'], $parentFolderId);
			}
			else
			{
				$item = $storage->moveDirectory($folderName, $targetFolder['extra']['id'], $parentFolderId);
			}

			if(!$item)
			{
				$this->errorCollection->addOne(new Error('Error in action move'));
				$this->sendJsonErrorResponse();
			}
			$event = new Event(Driver::INTERNAL_MODULE_ID, self::EVENT_ON_AFTER_DISK_FOLDER_UPDATE, array($item['extra']['id'], $item));
			$event->send();

			$this->sendJsonSuccessResponse($item);
		}
		else
		{
			//todo folder may make in storage root, but parentFolder not exist
			$item = $storage->addDirectory($folderName, $parentFolderId, array('originalTimestamp' => $this->request->getPost('originalTimestamp')));
		}

		if(empty($item))
		{
			$this->errorCollection->addOne(new Error('Error in makeDirectory'));
			$this->sendJsonErrorResponse();
		}
		$event = new Event(Driver::INTERNAL_MODULE_ID, self::EVENT_ON_AFTER_DISK_FOLDER_ADD, array($item['extra']['id'], $item));
		$event->send();

		$this->sendJsonSuccessResponse($item);
	}

	protected function processActionGetChunkSize()
	{
		$this->checkRequiredPostParams(array('name', 'size'));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$filename = $this->request->getPost('name');
		$size = (int)$this->request->getPost('size');

		if($size < 0)
		{
			throw new ArgumentException('Error in size');
		}

		$tmpFileManager = new Bitrix24Disk\UploadFileManager();
		$this->sendJsonSuccessResponse(array(
			'size' => $tmpFileManager->getChunkSize($filename, $size),
		));
	}

	protected function processActionRollbackUpload()
	{
		$this->enableIgnoreQuotaError();
		$this->checkRequiredPostParams(array('token'));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$tmpFileManager = new Bitrix24Disk\UploadFileManager();
		$tmpFileManager
			->setToken($this->request->getPost('token'))
			->setUser($this->getUser())
		;

		if(!$tmpFileManager->rollbackByToken())
		{
			$this->errorCollection->add($tmpFileManager->getErrors());
			$this->sendJsonErrorResponse();
		}
		$this->sendJsonSuccessResponse();
	}

	protected function processActionGetMaxUploadSize()
	{
		$maxUploadSize = min(CUtil::unformat(ini_get('post_max_size')), CUtil::unformat(ini_get('upload_max_filesize')));
		$maxUploadSize -= 1024 * 200;

		$this->sendJsonSuccessResponse(array(
			'size' => $maxUploadSize
		));
	}

	protected function processActionUpdate()
	{
		$this->checkRequiredPostParams(array('storageExtra', 'storageId', 'name'));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$tmpFile = $parentFolderId = $targetSectionId = $elementId = null;
		$storage = $this->getStorageObject($this->request->getPost('storageExtra'), $this->request->getPost('storageId'));
		$filename = $this->request->getPost('name');
		$token = $this->request->getPost('token');
		$inRoot = $this->request->getPost('inRoot') == 'true';
		$isUpdate = $this->request->getPost('update') == 'true';

		if($token)
		{
			$tmpFileManager = new Bitrix24Disk\UploadFileManager();
			$tmpFileManager
				->setToken($token)
				->setUser($this->getUser())
			;
			$tmpFile = $tmpFileManager->findUserSpecificTmpFileByToken();
			if(!$tmpFile)
			{
				throw new SystemException('Not found file by token');
			}
			$tmpFile->registerDelayedDeleteOnShutdown();
		}

		if(!$storage->isCorrectName($filename, $msg))
		{
			$tmpFile && ($tmpFile->delete());
			$this->sendJsonResponse(array(
				'status' => static::STATUS_DENIED,
				'message' => $msg,
			));
		}

		if($inRoot)
		{
			$storageExtra = $storage->getStorageExtra();
			$targetSectionId = $storageExtra['sectionId'];
			$parentFolderId = $storageExtra['sectionId'];
		}
		else
		{
			$this->checkRequiredPostParams(array('parentExtra'));
			if($this->errorCollection->hasErrors())
			{
				$this->sendJsonErrorResponse();
			}

			$parentFolderExtra = $storage->parseElementExtra($this->request->getPost('parentExtra'));
			$targetSectionId = $parentFolderExtra['id'];
			$parentFolderId = $parentFolderExtra['id'];

			$folder = Folder::loadById($parentFolderId);
			if($folder && $folder->isLink())
			{
				$parentFolderId = $folder->getRealObjectId();
			}
		}

		if($isUpdate)
		{
			$this->checkRequiredPostParams(array('id', 'version'));
			if($this->errorCollection->hasErrors())
			{
				$this->sendJsonErrorResponse();
			}
			
			$version = $this->request->getPost('version');
			$fileExtra = $storage->parseElementExtra($this->request->getPost('extra'));
			$elementId = $fileExtra['id'];

			$file = $storage->getFile($this->request->getPost('id'), $fileExtra);
			if(empty($file))
			{
				$this->sendJsonResponse(array(
					'status' => static::STATUS_NOT_FOUND,
				));
			}
			if($storage->compareVersion($file['version'], $version) > 0)
			{
				$file['status'] = static::STATUS_OLD_VERSION;
				$this->sendJsonResponse($file);
			}

			//todo simple check for move/rename
			if($filename != $file['extra']['name'] || $parentFolderId != $file['extra']['sectionId'])
			{
				if(!$storage->isUnique($filename, $parentFolderId, $opponentId))
				{
					$opponentFile = array();
					if($opponentId)
					{
						$opponentFile = $storage->getFile(null, array('id' => $opponentId), true);
					}
					$opponentFile['status'] = static::STATUS_NON_UNIQUE_NAME;

					$this->sendJsonResponse($opponentFile);
				}

				if($filename != $file['extra']['name'])
				{
					$file = $storage->renameFile($filename, $elementId, $parentFolderId);
					if(!$file)
					{
						$this->errorCollection->addOne(new Error('Error in rename (update) file'));
						$this->sendJsonErrorResponse();
					}
				}

				if($parentFolderId != $file['extra']['sectionId'])
				{
					$file = $storage->moveFile($filename, $elementId, $parentFolderId);
				}

				if(!$file)
				{
					$this->errorCollection->addOne(new Error('Error in move/rename (update) file'));
					$this->sendJsonErrorResponse();
				}

				if(!$tmpFile)
				{
					$this->sendJsonSuccessResponse($file);
				}
			}
			unset($file);

			if($tmpFile) //update content
			{
				$file = $storage->updateFile($filename, $elementId, $tmpFile, array('originalTimestamp' => $this->request->getPost('originalTimestamp')));
				if($file)
				{
					$event = new Event(Driver::INTERNAL_MODULE_ID, self::EVENT_ON_AFTER_DISK_FILE_UPDATE, array($file['extra']['id'], $file));
					$event->send();

					$this->sendJsonSuccessResponse($file);
				}

				$this->errorCollection->add($storage->getErrors());
				if($this->errorCollection->getErrorByCode($storage::ERROR_CREATE_FORK_FILE))
				{
					$urlManager = Driver::getInstance()->getUrlManager();
					/** @var Error $error */
					$error = $this->errorCollection->getErrorByCode($storage::ERROR_CREATE_FORK_FILE);
					/** @var File $forkedFile */
					$forkedFile = $error->getData();
					$this->errorCollection->clear();

					$this->errorCollection[] = new Error(
						$urlManager->getUrlFocusController('showObjectInGrid', array('objectId' => $forkedFile->getId())),
						self::ERROR_CREATE_FORK_FILE
					);

					$this->sendJsonErrorResponse();
				}

				$this->sendJsonResponse(array(
					'status' => static::STATUS_DENIED,
					'message'=> 'Error in updateFile',
				));
			}
		}
		elseif($tmpFile)
		{
			if(!$storage->isUnique($filename, $targetSectionId, $opponentId))
			{
				$opponentFile = array();
				if($opponentId)
				{
					$opponentFile = $storage->getFile(null, array('id' => $opponentId), true);
				}
				$opponentFile['status'] = static::STATUS_OLD_VERSION;

				$this->sendJsonResponse($opponentFile);
			}

			$newFile = $storage->addFile($filename, $targetSectionId, $tmpFile, array('originalTimestamp' => $this->request->getPost('originalTimestamp')));
			$tmpFile->delete();

			if($newFile)
			{
				$event = new Event(Driver::INTERNAL_MODULE_ID, self::EVENT_ON_AFTER_DISK_FILE_ADD, array($newFile['extra']['id'], $newFile));
				$event->send();

				$this->sendJsonSuccessResponse($newFile);
			}
			//else denied
		}

		$this->sendJsonResponse(array(
			'status' => static::STATUS_DENIED,
			'message'=> 'Error in add/update file',
		));
	}

	protected function processActionInitialize()
	{
		$this->enableIgnoreQuotaError();
		$userStorageId = $this->getUserStorageId();
		$storage = $this->getStorageObject();
		$storage->setStorageId($userStorageId);

		$userModel = User::loadById($this->getUser()->getId());
		if (!$userModel)
		{
			throw new ObjectNotFoundException("Could not find user model for id {$this->getUser()->getId()}");
		}

		$storageModel = Driver::getInstance()->getStorageByUserId($this->getUser()->getId());
		if(!$storageModel)
		{
			throw new ObjectNotFoundException("Could not find storage model for user {$this->getUser()->getId()}");
		}

		$isExtranetUser = $userModel->isExtranetUser();

		$this->sendJsonSuccessResponse(array(
			'userId' => (string)$this->getUser()->getID(),
			'userStorageId' => $storage->getStringStorageId(),
			'pathToUserLib' => $this->getPathToUserLib($userModel, $storageModel),
			'pathToDiscuss' => $this->getPathToDiscuss($userModel),
			'userStorageExtra' => array(
				'iblockId' => (string)$userStorageId['IBLOCK_ID'],
				'sectionId'=> (string)$userStorageId['IBLOCK_SECTION_ID'],
			),
			'isB24' => (bool)isModuleInstalled('bitrix24'),
			'isExtranetUser' => (bool)$isExtranetUser,

			'version'=> self::VERSION,
			'token_sid' => bitrix_sessid(),

			'defaultChunkSize' => Bitrix24Disk\UploadFileManager::DEFAULT_CHUNK_SIZE,
		));
	}

	protected function processActionGetDiskSpace()
	{
		$this->enableIgnoreQuotaError();

		$diskQuota = new \CDiskQuota();
		$freeSpace = $diskQuota->getDiskQuota();
		if($freeSpace === true)
		{
			$this->sendJsonResponse(array(
				'status' => static::STATUS_UNLIMITED,
				'freeSpace' => null,
				'diskSpace' => (float)COption::getOptionInt('main', 'disk_space', 0)*1024*1024,
			));
		}

		$this->sendJsonResponse(array(
			'status' => static::STATUS_LIMITED,
			'freeSpace' => $freeSpace === false? 0 : $freeSpace,
			'diskSpace' => (float)COption::getOptionInt('main', 'disk_space', 0)*1024*1024,
		));
	}
	
	protected function processActionGetDiskQuota()
	{
		$this->sendJsonResponse($this->getDiskQuotaData());
	}
	
	private function getPathToDiscuss(User $userModel)
	{
		$pathToDiscuss = '/';
		if($userModel->isExtranetUser())
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$siteId = CExtranet::getExtranetSiteID();
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$site = CSite::getArrayByID($siteId);
			if(!empty($site['DIR']))
			{
				return  '/' . trim($site['DIR'], '/') . '/';
			}
		}
		else
		{
			$userPage = \COption::getOptionString('socialnetwork', 'user_page', false, SITE_ID);
			if($userPage)
			{
				$pathToDiscuss = $userPage . $this->getUser()->getId() . '/blog/';
			}
		}

		return $pathToDiscuss;
	}

	private function getPathToUserLib(User $userModel, Storage $storageModel)
	{
		return $storageModel->getProxyType()->getBaseUrlFolderList();
	}

	protected function onBeforeActionGetPublicLink()
	{
		if(!Configuration::isEnabledExternalLink())
		{
			return new EventResult(EventResult::ERROR);
		}

		return new EventResult(EventResult::SUCCESS);
	}

	protected function processActionGetPublicLink()
	{
		$this->checkRequiredPostParams(array('id', 'extra', 'storageExtra', 'storageId'));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$id = $this->request->getPost('id');

		$storage = $this->getStorageObject($this->request->getPost('storageExtra'), $this->request->getPost('storageId'));
		$extra = $storage->parseElementExtra($this->request->getPost('extra'));

		$object = $storage->getFile($id, $extra);
		if (!$object)
		{
			$object = $storage->getDirectory($id, $extra);
		}

		//not found or we have new version
		if(!$object)
		{
			$this->sendJsonResponse(array(
				'status' => static::STATUS_NOT_FOUND,
			));
		}

		$publicLink = $storage->getPublicLink($object);
		if ($publicLink)
		{
			$storage->clearErrors();
		}

		$this->sendJsonSuccessResponse(array(
		   'link' => $publicLink,
		));
	}
	
	protected function processActionLock()
	{
		$this->checkRequiredPostParams(array('id', 'extra', 'storageExtra', 'storageId'));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$id = $this->request->getPost('id');

		$storage = $this->getStorageObject($this->request->getPost('storageExtra'), $this->request->getPost('storageId'));
		$extra = $storage->parseElementExtra($this->request->getPost('extra'));

		$file = $storage->getFile($id, $extra);
		if(!$file)
		{
			$this->sendJsonResponse(array(
				'status' => static::STATUS_NOT_FOUND,
			));
		}

		if($storage->lockFile($file))
		{
			$this->sendJsonSuccessResponse();
		}
		else
		{
			$this->sendJsonErrorResponse();
		}
	}

	protected function processActionUnlock()
	{
		$this->checkRequiredPostParams(array('id', 'extra', 'storageExtra', 'storageId'));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$id = $this->request->getPost('id');

		$storage = $this->getStorageObject($this->request->getPost('storageExtra'), $this->request->getPost('storageId'));
		$extra = $storage->parseElementExtra($this->request->getPost('extra'));

		$file = $storage->getFile($id, $extra);
		if(!$file)
		{
			$this->sendJsonResponse(array(
				'status' => static::STATUS_NOT_FOUND,
			));
		}

		if($storage->unlockFile($file))
		{
			$this->sendJsonSuccessResponse();
		}
		else
		{
			$this->sendJsonErrorResponse();
		}
	}

	/**
	 * @see getallheaders()
	 * @return array|false
	 */
	private function getAllHeaders()
	{
		if (!function_exists('getallheaders'))
		{
			$headers = array();
			foreach ($_SERVER as $name => $value)
			{
				if (substr($name, 0, 5) == 'HTTP_')
				{
					$headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
					$headers[$headerName] = $value;
				}
			}

			return $headers;
		}
		return getallheaders();
	}

	/**
	 * Return null, if not such range.
	 * Return array($start, $end, $length)
	 * @return array|null
	 */
	private function getContentRange()
	{
		$headers = $this->getAllHeaders();
		if(!$headers || !isset($headers['Content-Range']))
		{
			return false;
		}
		$contentRange = $headers['Content-Range'];
		if(!preg_match("/(\\d+)-(\\d+)\\/(\\d+)\$/", $contentRange, $match))
		{
			return null;
		}
		return array($match[1], $match[2], $match[3]);
	}

	/**
	 * @param $a
	 * @param $b
	 * @return int (1, -1, 0)
	 */
	private function compareStringVersion($a , $b)
	{
		$a = str_pad($a, strlen($b), '0', STR_PAD_LEFT);
		$b = str_pad($b, strlen($a), '0', STR_PAD_LEFT);

		return strcmp($a, $b);
	}

	private function enableIgnoreQuotaError()
	{
		$this->ignoreQuotaError = true;
	}

	/**
	 * @return int|null|string
	 */
	private function getVersionFromRequest()
	{
		$version = $this->request->getPost('version');
		if(!$version)
		{
			$version = $this->request->getQuery('version');
		}

		if(!$version)
		{
			$version = 0;

			return $version;
		}

		return $version;
	}

	/**
	 * @return Bitrix24Disk\PageState|null
	 */
	private function getPageStateFromRequest()
	{
		$pageStateString = $this->request->getPost('pageState');
		if(!$pageStateString)
		{
			$pageStateString = $this->request->getQuery('pageState');
		}

		if(!$pageStateString)
		{
			return null;
		}

		return Bitrix24Disk\PageState::createFromSignedString($pageStateString);
	}

	private function ignoreQuotaError()
	{
		return (bool)$this->ignoreQuotaError;
	}

	private function appendLastErrorToErrorCollection()
	{
		$lastError = $this->getApplication()->LAST_ERROR;
		if($lastError instanceof CAdminException)
		{
			$this->errorCollection->addOne(new Error($lastError->getMessages()));
		}
	}

	private function isQuotaError()
	{
		foreach($this->getApplication()->ERROR_STACK + array($this->getApplication()->LAST_ERROR) as $error)
		{
			if(!($error instanceof CAdminException))
			{
				continue;
			}
			if($error->getID() == 'QUOTA_BAD')
			{
				return true;
			}
			if(!is_array($error->getMessages()))
			{
				continue;
			}
			foreach ($error->getMessages() as $msg)
			{
				if($msg['id'] == 'QUOTA_BAD')
				{
					return true;
				}
			}
			unset($msg);
		}

		return false;
	}

	private function getUserStorageId()
	{
		$storage = Driver::getInstance()->getStorageByUserId($this->getUser()->getId());
		if(!$storage)
		{
			$storage = Driver::getInstance()->addUserStorage($this->getUser()->getId());
		}
		if(!$storage)
		{
			$this->errorCollection->addOne(new Error("Could not find storage for user {$this->getUser()->getId()}"));
			throw new SystemException("Could not find storage for user {$this->getUser()->getId()}");
		}

		return array(
			'IBLOCK_ID' => $storage->getId(),
			'IBLOCK_SECTION_ID' => $storage->getRootObjectId(),
		);
	}

	protected function getStorageObject(array $extra = array(), $storageId = '')
	{
		if(!empty($extra))
		{
			$extra = $this->storage->parseStorageExtra($extra);
			$this->storage->setStorageId(array(
				'IBLOCK_ID' => $extra['iblockId'],
				'IBLOCK_SECTION_ID' => $extra['sectionId'],
			));

			if($storageId && $storageId != $this->storage->getStringStorageId())
			{
				throw new ArgumentException('Wrong storage id!');
			}
			if(!$this->storage->getUserStorage())
			{
				throw new BadStorageAfterMigrateException;
			}
		}
		return $this->storage;
	}

	private function getDiskQuotaData()
	{
		$this->enableIgnoreQuotaError();
		$diskQuota = new \CDiskQuota();
		$quota = $diskQuota->getDiskQuota();
		if($quota === true)
		{
			return array(
				'status' => static::STATUS_UNLIMITED,
				'quota' => null,
			);
		}

		return array(
			'status' => static::STATUS_LIMITED,
			'quota' => $quota === false ? 0 : $quota,
		);
	}
}