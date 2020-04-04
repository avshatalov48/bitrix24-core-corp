<?php
use Bitrix\Main\Config\Option;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CWebDavDiskDispatcher
{
	const VERSION              = 10;

	const STATUS_SUCCESS       = 'success';
	const STATUS_DENIED        = 'denied';
	const STATUS_ERROR         = 'error';
	const STATUS_TOO_BIG       = 'too_big';
	const STATUS_NOT_FOUND     = 'not_found';
	const STATUS_OLD_VERSION   = 'old_version';
	const STATUS_NO_SPACE      = 'no_space';
	const STATUS_UNLIMITED     = 'unlimited';
	const STATUS_LIMITED       = 'limited';
	const STATUS_CHUNK_ERROR   = 'chunk_error';
	const STATUS_ACCESS_DENIED = 'access_denied';
	const STATUS_TRY_LATER     = 'try_later';
	const STATUS_LOCKED        = 'locked';

	protected $ignoreQuotaError = false;
	protected $enableDiskModule = false;
	protected $freezeModule     = false;
	protected static $dataDeletingMark = array();

	const ON_AFTER_DISK_FIRST_USAGE_BY_DAY = 'OnAfterDiskFirstUsageByDay';
	const ON_AFTER_DISK_FILE_UPDATE        = 'OnAfterDiskFileUpdate';
	const ON_AFTER_DISK_FILE_ADD           = 'OnAfterDiskFileAdd';
	const ON_AFTER_DISK_FILE_DELETE        = 'OnAfterDiskFileDelete';
	const ON_AFTER_DISK_FOLDER_UPDATE      = 'OnAfterDiskFolderUpdate';
	const ON_AFTER_DISK_FOLDER_ADD         = 'OnAfterDiskFolderAdd';
	const ON_AFTER_DISK_FOLDER_DELETE      = 'OnAfterDiskFolderDelete';
	/** @var  CWebDavAbstractStorage */
	protected $lastStorage;

	public function __construct($enableDiskModule = true)
	{
		$this->enableDiskModule = Option::get('disk', 'successfully_converted', false) == 'Y' && CModule::includeModule('disk');
		$this->freezeModule = !$this->enableDiskModule && Option::get('disk', 'process_converted', false) == 'Y';
	}

	public static function getEventNameList()
	{
		return array(
			static::ON_AFTER_DISK_FIRST_USAGE_BY_DAY,
			static::ON_AFTER_DISK_FILE_UPDATE,
			static::ON_AFTER_DISK_FILE_ADD,
			static::ON_AFTER_DISK_FILE_DELETE,
			static::ON_AFTER_DISK_FOLDER_UPDATE,
			static::ON_AFTER_DISK_FOLDER_ADD,
			static::ON_AFTER_DISK_FOLDER_DELETE,
		);
	}

	public static function storeUsageDisk()
	{
		if(static::getDesktopDiskVersion(true) === 0)
		{
			return false;
		}

		global $APPLICATION;
		if(!$APPLICATION->get_cookie("WD_DISK_USAGE"))
		{
			list($y, $m, $d) = explode('-', date('Y-m-d'));

			//end of current day;
			$APPLICATION->set_cookie("WD_DISK_USAGE", 'Y', mktime(0, 0, 0, $m, $d + 1, $y));
			CWebDavTools::runEvent(static::ON_AFTER_DISK_FIRST_USAGE_BY_DAY, array());

			return true;
		}
		return false;
	}

	/**
	 * @return float
	 */
	public static function getTimestampFloat()
	{
		return round(microtime(true) * 1000);
	}

	public static function getVersion()
	{
		return static::VERSION;
	}

	/**
	 * @param bool $strictDisk Non ajax request from Desktop Chrome
	 * @return bool|int
	 */
	public static function getDesktopDiskVersion($strictDisk = false)
	{
		if(isset($_SERVER['HTTP_USER_AGENT']) && preg_match('%Bitrix24.Disk/([0-9\.]+)%i', $_SERVER['HTTP_USER_AGENT'], $m))
		{
			if($strictDisk && strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false)
			{
				return 0;
			}
			return $m[1];
		}
		return 0;
	}

	/**
	 * @param $iblockId
	 * @param $sectionId
	 * @return array
	 */
	public static function getSubscribersOnSection($iblockId, $sectionId)
	{
		$userIds = $sectionIds = array();
		foreach (CWebDavSymlinkHelper::getNavChain($iblockId, $sectionId) as $sectionChain)
		{
			$sectionIds[] = $sectionChain['ID'];
			if (isset($sectionChain['DEPTH_LEVEL']) && $sectionChain['DEPTH_LEVEL'] == 1)
			{
				//this is owner in 98%
				$userIds[] = $sectionChain['CREATED_BY'];
			}
		}
		unset($sectionChain);


		$query = \Bitrix\Webdav\FolderInviteTable::getList(array(
			'select' => array('INVITE_USER_ID'),
			'filter' => array(
				'SECTION_ID' => $sectionIds,
			),
		));
		while ($row = $query->fetch())
		{
			$userIds[] = $row['INVITE_USER_ID'];
		}

		return $userIds;
	}

	public function enableIgnoreQuotaError()
	{
		$this->ignoreQuotaError = true;
	}

	public function disableIgnoreQuotaError()
	{
		$this->ignoreQuotaError = false;
	}

	public function ignoreQuotaError()
	{
		return (bool)$this->ignoreQuotaError;
	}

	/**
	 * @param string $message
	 * @return array
	 */
	public function sendError($message)
	{
		return $this->sendResponse(array(
			'status' => static::STATUS_ERROR,
			'message' => $message,
		));
	}

	public function sendSuccess(array $response = array())
	{
		$response['status'] = static::STATUS_SUCCESS;
		return $this->sendResponse($response);
	}

	public function sendResponse($response)
	{
		if($this->freezeModule)
		{
			return array(
				'status' => static::STATUS_NOT_FOUND,
				'message' => 'Run migrate',
			);
		}

		if(!$this->ignoreQuotaError() && $this->isQuotaError())
		{
			return array(
				'status' => static::STATUS_NO_SPACE,
			);
		}

		$detail = $this->getErrors();
		if($detail)
		{
			$response['detail'] = $detail;
		}
		return $response;
	}

	public function getErrors()
	{
		$detail = null;
		if($this->lastStorage && method_exists($this->lastStorage, 'getErrors'))
		{
			$detail = array();
			foreach($this->lastStorage->getErrors() as $error)
			{
				$detail[] = array(
					'message' => $error->getMessage(),
					'code' => $error->getCode(),
				);
			}
		}

		return $detail;
	}

	/**
	 * @return CAllUser
	 */
	protected function getUser()
	{
		global $USER;

		return $USER;
	}

	/**
	 * @return CDatabase
	 */
	protected function getDb()
	{
		global $DB;

		return $DB;
	}

	/**
	 * @return CAllMain
	 */
	protected static function getApplication()
	{
		global $APPLICATION;

		return $APPLICATION;
	}

	/**
	 * @param array  $extra
	 * @param string $storageId
	 * @throws Exception
	 * @return CWebDavAbstractStorage
	 */
	protected function getStorageObject(array $extra = array(), $storageId = '')
	{
		$storage = $this->enableDiskModule? new CDiskStorage() : new CWebDavStorageCore();
		if(!empty($extra))
		{
			$extra = $storage->parseStorageExtra($extra);
			$storage->setStorageId(array(
				'IBLOCK_ID' => $extra['iblockId'],
				'IBLOCK_SECTION_ID' => $extra['sectionId'],
			));

			if($storageId)
			{
				if($storageId != $storage->getStringStorageId())
				{
					throw new Exception('Wrong storage id!');
				}
			}

			if($storage instanceof CDiskStorage)
			{
				if(!$storage->getUserStorage())
				{
					throw new CWebDavBadStorageAfterMigrateException;
				}
			}

		}
		$this->lastStorage = $storage;

		return $storage;
	}

	protected function checkRequiredParams(array $target, array $required)
	{
		$success = true;
		foreach ($required as $item)
		{
			if(!isset($target[$item]) || (!$target[$item] && !(is_string($target[$item]) && strlen($target[$item]))))
			{
				$success = false;
				break;
			}
		}
		if(!$success)
		{
			throw new Exception("Wrong params: {{$item}}");
		}

		return;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	protected function getUserStorageId()
	{
		if($this->enableDiskModule)
		{
			$storage = \Bitrix\Disk\Driver::getInstance()->getStorageByUserId($this->getUser()->getId());
			if(!$storage)
			{
				$storage = \Bitrix\Disk\Driver::getInstance()->addUserStorage($this->getUser()->getId());
			}
			if($storage)
			{
				return array(
					'IBLOCK_ID' => $storage->getId(),
					'IBLOCK_SECTION_ID' => $storage->getRootObjectId(),
				);
			}
		}
		$userFilesOptions = COption::getOptionString('webdav', 'user_files', null);
		if($userFilesOptions == null)
		{
			throw new Exception('Where are options "user_files"?');
		}
		$userFilesOptions = unserialize($userFilesOptions);
		$iblockId = $userFilesOptions[CSite::getDefSite()]['id'];
		$userSectionId = CWebDavIblock::getRootSectionIdForUser($iblockId, $this->getUser()->getId());
		if(!$userSectionId)
		{
			throw new Exception('Wrong section for user ' . $this->getUser()->getLastName());
		}

		return array(
			'IBLOCK_ID' => $iblockId,
			'IBLOCK_SECTION_ID' => $userSectionId,
		);
	}

	public function getStorageList()
	{
	}

	public function getSubscriptionsStorageList()
	{
	}

	//todo version is long int
	public static function convertFromExternalVersion($version)
	{
		if(substr($version, -3, 3) === '000')
		{
			return substr($version, 0, -3);
		}
		return $version;
	}

	public static function convertToExternalVersion($version)
	{
		return ((string)$version) . '000';
	}

	public function processActionSnapshot(array $params = array())
	{
		$this->enableIgnoreQuotaError();
		//todo version is long int
		$version = $params['version'];

		$userStorageId = $this->getUserStorageId();
		$storage = $this->getStorageObject();
		$items = $storage
			->setStorageId($userStorageId)
			->getSnapshot($version)
		;
		$quota = $this->processActionGetDiskQuota();

		return $this->sendResponse(array('quota' => $quota, 'snapshot' => $items));
	}


	/**
	 * Send wedav-notify to owner and subscribes of elements.
	 * @param null|array   $element
	 * @param null|array   $section
	 * @param string $debug
	 * @return bool
	 */
	public static function sendEventToOwners($element = null, $section = null, $debug = '')
	{
		if($element && is_array($element) && isset($element['IBLOCK_SECTION_ID']))
		{
			$sectionId = $element['IBLOCK_SECTION_ID'];
			$iblockId = $element['IBLOCK_ID'];
		}
		elseif(isset($section['IBLOCK_ID'], $section['ID']))
		{
			$sectionId = $section['ID'];
			$iblockId = $section['IBLOCK_ID'];
		}
		else
		{
			return false;
		}
		static::sendChangeStatus(self::getSubscribersOnSection($iblockId, $sectionId), 'sec_' . $debug . '_puper');

		return true;
	}

	/**
	 * @param array $section
	 * @param bool  $returnUserAndSection todo rework
	 * @return array|bool|mixed
	 * @deprecated
	 */
	protected static function findOwnerIdSection(array $section, $returnUserAndSection = false)
	{
		if(isset($section['DEPTH_LEVEL']) && $section['DEPTH_LEVEL'] == 1)
		{
			$sectionOwnerElement = $section;
		}
		else
		{
			//todo so normally to look for root section?
			$sectionOwnerElement = CIBlockSection::GetList(array('LEFT_MARGIN' => 'DESC'), array(
				'IBLOCK_ID'         => $section['IBLOCK_ID'],
				'DEPTH_LEVEL'       => 1,
				'IBLOCK_SECTION_ID' => null,
				'!LEFT_MARGIN'      => $section['LEFT_MARGIN'],
				'!RIGHT_MARGIN'     => $section['RIGHT_MARGIN'],
			), false, array('ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'CREATED_BY', 'NAME'))->fetch();
		}
		//$user = CUser::GetById($sectionOwnerElement['CREATED_BY'])->fetch();
		if(empty($sectionOwnerElement))
			return false;

		return !$returnUserAndSection? $sectionOwnerElement['CREATED_BY'] : array($sectionOwnerElement['CREATED_BY'], $sectionOwnerElement);
	}


	/**
	 * @param int|array $userIds
	 * @param string $debug
	 */
	public static function sendChangeStatus($userIds, $debug = '')
	{
		static::sendEvent($userIds, array(
			'params' => array(
				'change' => true,
				'timestamp' => static::getTimestampFloat(),
				//'debug' => $debug,
			),
		));
	}

	/**
	 * @param int|array $userIds
	 * @param array $data
	 * @return bool
	 */
	public static function sendEvent($userIds, array $data)
	{
		if(empty($userIds))
		{
			return false;
		}
		if(!CModule::IncludeModule('pull'))
		{
			return false;
		}
		$data['module_id'] = 'webdav';
		$data['command'] = 'notify';
		if(!is_array($userIds))
		{
			$userIds = array($userIds);
		}
		foreach ($userIds as $userId)
		{
			CPullStack::AddByUser($userId, $data);
		}
		unset($userId);
	}

	public function processActionDownload(array $params)
	{
		$this->checkRequiredParams($params, array('id', 'version', 'extra', 'storageExtra', 'storageId'));

		$id = $params['id'];
		$version = $params['version'];

		$storage = $this->getStorageObject($params['storageExtra'], $params['storageId']);
		$extra = $storage->parseElementExtra($params['extra']);

		$file = $storage->getFile($id, $extra);
		//not found or we have new version
		if( !$file || (!isset($file['version']) || $storage::compareVersion($file['version'], $version) != 0) )
		{
			return $this->sendResponse(array(
				'status' => static::STATUS_NOT_FOUND,
			));
		}
		else
		{
			if(!$storage->sendFile($file))
			{
				return $this->sendResponse(array(
					'status' => static::STATUS_NOT_FOUND,
					'message' => 'Not found source file',
				));
			}
		}
	}

	public function processActionDelete(array $params)
	{
		$this->enableIgnoreQuotaError();
		$this->checkRequiredParams($params, array('id', 'version', 'extra', 'storageExtra', 'storageId')); //isDirectory

		$id = $params['id'];
		$version = $params['version'];
		$isDirectory = (bool)$params['isDirectory'];

		$lastVersion = null;
		$isDirectory = (bool)$isDirectory;
		$storage = $this->getStorageObject($params['storageExtra'], $params['storageId']);
		$extra = $storage->parseElementExtra($params['extra']);

		$element = $isDirectory?
			$storage->getDirectory($id, $extra):
			$storage->getFile($id, $extra);

		//todo check invite, if file under symlink
		if($element && $extra['inSymlink'])
		{
			$chain = CWebDavSymlinkHelper::getNavChain($element['extra']['iblockId'], $element['extra']['sectionId']);
			$sectionIds = array();
			foreach ($chain as $item)
			{
				$sectionIds[] = $item['ID'];
			}
			unset($item, $chain);

			//has current user invite on this section?
			$query = \Bitrix\Webdav\FolderInviteTable::getList(array(
				'select' => array('ID'),
				'filter' => array(
					'INVITE_USER_ID' => $this->getUser()->getId(),
					'IS_APPROVED' => true,
					'IS_DELETED' => false,
					'SECTION_ID' => $sectionIds,
				),
			));
			$row = $query->fetch();
			if(!isset($row['ID']))
			{
				return $this->sendResponse(array(
					'status' => static::STATUS_NOT_FOUND,
					'message'=> 'Link detached'
				));
			}
		}

		if($element)
		{
			if($storage::compareVersion($element['version'], $version) > 0)
			{
				$element['status'] = static::STATUS_OLD_VERSION;
				return $this->sendResponse($element);
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
			CWebDavTools::runEvent(($isDirectory? static::ON_AFTER_DISK_FOLDER_DELETE : static::ON_AFTER_DISK_FILE_DELETE), array($element['extra']['id'], $element));
			return $this->sendSuccess(array('version' => $this->convertToExternalVersion((string)$lastVersion)));
		}
		return $this->sendResponse(array('status' => static::STATUS_NOT_FOUND));
	}

	public function processActionDirectory(array $params)
	{
		$this->checkRequiredParams($params, array('name', 'storageExtra', 'storageId'));

		$folderName = $params['name'];
		$inRoot = (bool)$params['inRoot'];
		$isUpdate = (bool)$params['update'];

		$storage = $this->getStorageObject($params['storageExtra'], $params['storageId']);

		if(!$storage->isCorrectName($folderName, $msg))
		{
			return $this->sendResponse(array(
				'status' => static::STATUS_DENIED,
				'message' => $msg,
			));
		}

		$parentFolderId = null;
		if(!$inRoot)
		{
			$parentFolderExtra = $storage->parseElementExtra($params['parentExtra']);
			$parentFolderId = $parentFolderExtra['id'];
			//$parentFolderVersion = $_POST['version'];
		}

		if($isUpdate)
		{
			$this->checkRequiredParams($params, array('id', 'version'));
			$id = $params['id'];
			$version = $params['version'];

			$folderExtra = $storage->parseElementExtra($params['extra']);
			$targetFolder = $storage->getDirectory($id, $folderExtra);
			if(empty($targetFolder))
			{
				return $this->sendError('Not found directory to update');
			}

			$storageKey = $storage->getStorageId();
			//it is the same directory todo this logic $storage->moveDirectory, but ....we have many query. Or refactor signature
			if($targetFolder['extra']['sectionId'] == $parentFolderId && $folderName == $targetFolder['name'])
			{
				return $this->sendSuccess($targetFolder);
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
				return $this->sendError('Error in action move');
			}
			CWebDavTools::runEvent(static::ON_AFTER_DISK_FOLDER_UPDATE, array($item['extra']['id'], $item));
			return $this->sendSuccess($item);
		}
		else
		{
			//todo folder may make in storage root, but parentFolder not exist
			$item = $storage->addDirectory($folderName, $parentFolderId);
		}

		if(empty($item))
		{
			return $this->sendError('Error in makeDirectory');
		}
		CWebDavTools::runEvent(static::ON_AFTER_DISK_FOLDER_ADD, array($item['extra']['id'], $item));
		return $this->sendSuccess($item);
	}

	/**
	 * @param array $params
	 * @return array
	 * @throws Exception
	 */
	public function processActionGetChunkSize(array $params)
	{
		if($this->getDesktopDiskVersion() <= 0)
		{
			throw new Exception('Wrong action');
		}
		$this->checkRequiredParams($params, array('name', 'size'));

		$filename = $params['name'];
		$size = (int)$params['size'];

		if($size < 0)
		{
			throw new Exception('Error in size');
		}
		$chunkSizeForCloud = $this->getChunkSizeForCloud(array(
			'name' => $filename,
			'fileSize' => $size,
		));
		if($chunkSizeForCloud !== false)
		{
			return $this->sendSuccess(array(
				'size' => $chunkSizeForCloud,
			));
		}
		return $this->processActionGetMaxUploadSize();
	}

	/**
	 * @param array $params
	 * @return bool|integer
	 */
	protected function getChunkSizeForCloud(array $params)
	{
		$bucket = $this->findBucketForFile($params);
		return $bucket !== false? $bucket->getService()->getMinUploadPartSize() : false;
	}

	/**
	 * If enable module clouds, then find bucket for file
	 * @param array $params
	 * @return bool|CCloudStorageBucket
	 */
	protected function findBucketForFile(array $params)
	{
		if(!CModule::IncludeModule("clouds"))
		{
			return false;
		}
		$bucket = CCloudStorage::FindBucketForFile(array('FILE_SIZE' => $params['fileSize'], 'MODULE_ID' => 'iblock'), $params['name']);
		if($bucket === null || !$bucket->init())
		{
			return false;
		}
		return $bucket;
	}

	public function processActionGetMaxUploadSize()
	{
		$maxUploadSize = min(CUtil::unformat(ini_get('post_max_size')), CUtil::unformat(ini_get('upload_max_filesize')));
		$maxUploadSize -= 1024 * 200;

		if($maxUploadSize > 104857600) //100mb
		{
			$maxUploadSize = 104857600;
		}

		return $this->sendSuccess(array(
			'size' => $maxUploadSize
		));
	}

	public function processActionUpload(array $params)
	{
		$storage = $this->getStorageObject();

		if($this->getDesktopDiskVersion() > 0)
		{
			$this->checkRequiredParams($params, array('name'));
			$filename = $params['name'];
		}

		if(
			$storage::compareVersion(
				$_SERVER['CONTENT_LENGTH'],
				(string)min(CUtil::unformat(ini_get('upload_max_filesize')), CUtil::unformat(ini_get('post_max_size')))) > 0
		)
		{
			return $this->sendResponse(array('status' => static::STATUS_TOO_BIG));
		}
		if(empty($_FILES['file']) || !is_array($_FILES['file']))
		{
			throw new Exception('Please load file!');
		}

		list($startRange, $endRange, $fileSize) = $this->getContentRange();
		if($startRange !== null)
		{
			if( ($endRange - $startRange + 1) != $_FILES['file']['size'] )
			{
				return $this->sendResponse(array(
					'status' => static::STATUS_CHUNK_ERROR,
					'message'=> 'Size of file: ' . $_FILES['file']['size'] . ' not equals size of chunk: ' . ($endRange - $startRange + 1) . '',
				));
			}

			if($startRange == 0)
			{
				//attempt to decide: cloud? not cloud?
				$bucket = $this->findBucketForFile(array(
					'name' => $filename,
					'fileSize' => $fileSize,
				));
				if($bucket !== false)
				{
					$tmpFile = CWebDavTmpFile::buildFromDownloaded($_FILES['file']);
					list($tmpFile->width, $tmpFile->height) = CFile::getImageSize($tmpFile->getAbsolutePath());

					$newFile = clone $tmpFile;
					$newFile->filename = $filename;
					$newFile->isCloud = true;
					$newFile->bucketId = $bucket->ID;
					$newFile->append($tmpFile, compact(
						'startRange', 'endRange', 'fileSize'
					));
					if(!$newFile->save())
					{
						throw new Exception('Error in DB');
					}
				}
				else
				{
					//simple upload
					$newFile = $this->createNewFile();
				}
				return $this->sendSuccess(array(
					'token' => $newFile->name,
				));
			}
			else
			{
				//if run resumable upload we needed token.
				$this->checkRequiredParams($params, array('token'));
				if(!($tmpResumableFile = CWebDavTmpFile::buildByName($params['token'])))
				{
					return $this->sendResponse(array(
						'status' => static::STATUS_CHUNK_ERROR,
						'message'=> 'Not found file by token',
					));
				}
				$success = $tmpResumableFile->append(CWebDavTmpFile::buildFromDownloaded($_FILES['file']), compact(
					'startRange', 'endRange', 'fileSize'
				));
				if($success)
				{
					return $this->sendSuccess(array(
						'token' => $tmpResumableFile->name,
					));
				}
			}
		}
		else
		{
			//simple upload
			$newFile = $this->createNewFile();

			return $this->sendSuccess(array(
				'token' => $newFile->name,
			));
		}
	}

	protected function createNewFile()
	{
		$tmpFile = CWebDavTmpFile::buildFromDownloaded($_FILES['file']);
		if(!$tmpFile->save())
		{
			throw new Exception('Error in DB');
		}
		return $tmpFile;
	}

	/**
	 * Return false, if not such range.
	 * Return array($start, $end, $length)
	 * @return array|bool
	 */
	protected function getContentRange()
	{
		$headers = $this->getAllHeaders();
		if(!$headers || !isset($headers['Content-Range']))
		{
			return false;
		}
		$contentRange = $headers['Content-Range'];
		if(!preg_match("/(\\d+)-(\\d+)\\/(\\d+)\$/", $contentRange, $match))
		{
			return false;
		}
		return array($match[1], $match[2], $match[3]);
	}

	public function processActionRollbackUpload(array $params)
	{
		$this->enableIgnoreQuotaError();
		$this->checkRequiredParams($params, array('token'));

		$token = $params['token'];
		if(!($tmpFile = CWebDavTmpFile::buildByName($token)))
		{
			throw new Exception('Not found file by token');
		}
		if($tmpFile->delete())
		{
			return $this->sendSuccess();
		}
		return $this->sendError('Bad attempt to delete token');
	}

	public function processActionUpdate(array $params)
	{
		$this->checkRequiredParams($params, array('storageExtra', 'storageId', 'name'));

		$tmpFile = $parentFolderId = $targetSectionId = $elementId = null;
		$storage = $this->getStorageObject($params['storageExtra'], $params['storageId']);
		$filename = $params['name'];
		$token = empty($params['token'])? null : $params['token'];
		$inRoot = (bool)$params['inRoot'];
		$isUpdate = (bool)$params['update'];

		if($token && !($tmpFile = CWebDavTmpFile::buildByName($token)))
		{
			throw new Exception('Not found file by token');
		}

		if(!$storage->isCorrectName($filename, $msg))
		{
			$tmpFile && ($tmpFile->delete());
			return $this->sendResponse(array(
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
			$this->checkRequiredParams($params, array('parentExtra'));

			$parentFolderExtra = $storage->parseElementExtra($params['parentExtra']);
			$targetSectionId = $parentFolderExtra['id'];
			$parentFolderId = $parentFolderExtra['id'];
		}

		if($isUpdate)
		{
			$this->checkRequiredParams($params, array('id', 'version'));
			$version = $params['version'];
			$fileExtra = $storage->parseElementExtra($params['extra']);
			$elementId = $fileExtra['id'];

			$file = $storage->getFile($params['id'], $fileExtra);
			if(empty($file))
			{
				return $this->sendResponse(array(
					'status' => static::STATUS_NOT_FOUND,
				));
			}
			if($storage::compareVersion($file['version'], $version) > 0)
			{
				$file['status'] = static::STATUS_OLD_VERSION;
				return $this->sendResponse($file);
			}

			//todo simple check for move/rename
			if($filename != $file['extra']['name'] || $parentFolderId != $file['extra']['sectionId'])
			{
				if(!$storage->isUnique($filename, $parentFolderId))
				{
					$file['status'] = static::STATUS_OLD_VERSION;
					return $this->sendResponse($file);
				}

				if($filename != $file['extra']['name'])
				{
					$file = $storage->renameFile($filename, $elementId, $parentFolderId);
					if(!$file)
					{
						return $this->sendError('Error in rename (update) file');
					}
				}

				if($parentFolderId != $file['extra']['sectionId'])
				{
					$file = $storage->moveFile($filename, $elementId, $parentFolderId);
				}

				if(!$file)
				{
					return $this->sendError('Error in move/rename (update) file');
				}

				if(!$tmpFile)
				{
					return $this->sendSuccess($file);
				}
			}
			unset($file);

			if($tmpFile) //update content
			{
				$file = $storage->updateFile($filename, $elementId, $tmpFile);
				if($file)
				{
					CWebDavTools::runEvent(static::ON_AFTER_DISK_FILE_UPDATE, array($file['extra']['id'], $file));
					return $this->sendSuccess($file);
				}
				return $this->sendResponse(array(
					'status' => static::STATUS_DENIED,
					'message'=> 'Error in updateFile',
				));
			}
		}
		else
		{
			if(!$storage->isUnique($filename, $targetSectionId, $opponentId))
			{
				$opponentFile = array();
				if($opponentId)
				{
					$opponentFile = $storage->getFile(null, array('id' => $opponentId), true);
				}
				$opponentFile['status'] = static::STATUS_OLD_VERSION;

				return $this->sendResponse($opponentFile);
			}
			$newFile = $storage->addFile($filename, $targetSectionId, $tmpFile);
			if($newFile)
			{
				CWebDavTools::runEvent(static::ON_AFTER_DISK_FILE_ADD, array($newFile['extra']['id'], $newFile));
				return $this->sendSuccess($newFile);
			}
			//else denied
		}

		return $this->sendResponse(array(
			'status' => static::STATUS_DENIED,
			'message'=> 'Error in add/update file',
		));
	}

	/**
	 * @return bool
	 */
	private function isExtranetUser()
	{
		static $really = null;

		if($really === null)
		{
			$really = !CWebDavTools::isIntranetUser($this->getUser()->getId()) && CModule::includeModule("extranet");
		}
		return $really;
	}

	/**
	 * @return string
	 */
	private function getPathToDiscuss()
	{
		$pathToDiscuss = '/';
		if($this->isExtranetUser())
		{
			$siteId = CExtranet::getExtranetSiteID();
			$site = CSite::getArrayByID($siteId);
			if(!empty($site['DIR']))
			{
				$pathToDiscuss =  '/' . trim($site['DIR'], '/') . '/';
			}
		}

		return $pathToDiscuss;
	}

	/**
	 * @return string
	 */
	private function getPathToUserLib()
	{
		$siteId = SITE_ID;
		if($this->isExtranetUser())
		{
			$siteId = CExtranet::getExtranetSiteID();
		}
		$pathToUserLib = COption::getOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/', $siteId);
		if (!empty($pathToUserLib))
		{
			$pathToUserLib = str_replace(array('#USER_ID#', '#user_id#'), $this->getUser()->getID(), $pathToUserLib);
			$pathToUserLib = $pathToUserLib . 'files/lib/';
		}
		else
		{
			$pathToUserLib = '/company/personal/user/' . $this->getUser()->getId() . '/files/lib/';
		}

		return $pathToUserLib;
	}

	public function processActionInitialize()
	{
		$this->enableIgnoreQuotaError();
		$userStorageId = $this->getUserStorageId();
		$storage = $this->getStorageObject();
		$storage->setStorageId($userStorageId);

		return $this->sendResponse(array(
			'status' => static::STATUS_SUCCESS,
			'userId' => (string)$this->getUser()->getID(),
			'userStorageId' => $storage->getStringStorageId(),
			'pathToUserLib' => $this->getPathToUserLib(),
			'pathToDiscuss' => $this->getPathToDiscuss(),
			'userStorageExtra' => array(
				'iblockId' => (string)$userStorageId['IBLOCK_ID'],
				'sectionId'=> (string)$userStorageId['IBLOCK_SECTION_ID'],
			),
			'isB24' => (bool)IsModuleInstalled('bitrix24'),
			'isExtranetUser' => (bool)$this->isExtranetUser(),
			'version'=> static::VERSION,
		));
	}

	public function processActionGetDiskSpace()
	{
		$this->enableIgnoreQuotaError();
		$quota = new CDiskQuota;
		$freeSpace = $quota->GetDiskQuota();
		if($freeSpace === true)
		{
			return $this->sendResponse(array(
				'status' => static::STATUS_UNLIMITED,
				'freeSpace' => null,
				'diskSpace' => (float)COption::GetOptionInt('main', 'disk_space', 0)*1024*1024,
			));
		}

		return $this->sendResponse(array(
			'status' => static::STATUS_LIMITED,
			'freeSpace' => $freeSpace === false? 0 : $freeSpace,
			'diskSpace' => (float)COption::GetOptionInt('main', 'disk_space', 0)*1024*1024,
		));
	}

	public function processActionGetDiskQuota()
	{
		$this->enableIgnoreQuotaError();
		$diskQuota = new CDiskQuota;
		$quota = $diskQuota->GetDiskQuota();
		if($quota === true)
		{
			return $this->sendResponse(array(
				'status' => static::STATUS_UNLIMITED,
				'quota' => null,
			));
		}

		return $this->sendResponse(array(
			'status' => static::STATUS_LIMITED,
			'quota' => $quota === false? 0 : $quota,
		));
	}

	public function processActionGetPublicLink(array $params)
	{
		$this->checkRequiredParams($params, array('id', 'extra', 'storageExtra', 'storageId'));

		$id = $params['id'];

		$storage = $this->getStorageObject($params['storageExtra'], $params['storageId']);
		$extra = $storage->parseElementExtra($params['extra']);

		$file = $storage->getFile($id, $extra);
		//not found or we have new version
		if(!$file)
		{
			return $this->sendResponse(array(
				'status' => static::STATUS_NOT_FOUND,
			));
		}

		return $this->sendSuccess(array(
			'link' => $storage->getPublicLink($file),
		));
	}

	protected function isQuotaError()
	{
		foreach($this->getApplication()->ERROR_STACK + array($this->getApplication()->LAST_ERROR) as $error)
		{
			if(!($error instanceof CAdminException))
			{
				continue;
			}
			if($error->GetID() == 'QUOTA_BAD')
			{
				return true;
			}
			if(!is_array($error->GetMessages()))
			{
				continue;
			}
			foreach ($error->GetMessages() as $msg)
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

	//to add an element for a deleting mark
	public static function addElementForDeletingMark(array $data, $dataDetermineOwner = array(), $isSection = true)
	{
		static $cacheDataDetermineOwner = array();

		if(!$isSection && empty($data['IBLOCK_SECTION_ID']))
		{
			return false;
		}

		$hashKey = empty($dataDetermineOwner)? false : md5(serialize($dataDetermineOwner));
		if($hashKey && isset($cacheDataDetermineOwner[$hashKey]))
		{
			list($userIds, $sectionOwnersElement) = $cacheDataDetermineOwner[$hashKey];
		}
		else
		{
			if($isSection)
			{
				$sectionId = $data['ID'];
				$iblockId = $data['IBLOCK_ID'];
			}
			else
			{
				$sectionId = $data['IBLOCK_SECTION_ID'];
				$iblockId = $data['IBLOCK_ID'];
			}

			$sectionOwnersElement = array();
			$userIds = self::getSubscribersOnSection($iblockId, $sectionId);
			foreach ($userIds as $userId)
			{
				$sectionOwnersElement[] = CWebDavIblock::getRootSectionDataForUser($userId);
			}
			unset($userId);

			if($hashKey)
			{
				$cacheDataDetermineOwner[$hashKey] = array($userIds, $sectionOwnersElement);
			}
		}

		if(empty($sectionOwnersElement))
		{
			return false;
		}

		$data['isSection'] = (bool)$isSection;
		$data['ownerData'] = array($userIds, $sectionOwnersElement);
		static::$dataDeletingMark[] = $data;

		return true;
	}

	public static function clearDeleteBatch()
	{
		static::$dataDeletingMark = array();
	}

	public static function markDeleteBatch($deleteInviteOnSection = true)
	{
		if(empty(static::$dataDeletingMark))
		{
			return false;
		}

		/** @var CWebDavDiskDispatcher $component */
		$component = new static();
		/** @var CWebDavAbstractStorage $storage  */
		$storage = $component->getStorageObject();

		global $USER;
		$userId = $USER->getId();
		$keeper = array();
		$sectionIds = array();
		foreach (static::$dataDeletingMark as $key => $data)
		{
			list($userIds, $sectionOwnersElement) = $data['ownerData'];

			foreach ($sectionOwnersElement as $ownerSection)
			{
				if(empty($ownerSection['IBLOCK_ID']) || empty($ownerSection['SECTION_ID']))
				{
					continue;
				}
				$storage->setStorageId(array(
					'IBLOCK_ID' => $ownerSection['IBLOCK_ID'],
					'IBLOCK_SECTION_ID' => $ownerSection['SECTION_ID'],
				));

				$uniqueId = $storage->generateId(array('FILE' => !$data['isSection'], 'ID' => $data['ID']));
				$keeper[] = array(
					'IBLOCK_ID' => $ownerSection['IBLOCK_ID'],
					'SECTION_ID'=> $ownerSection['SECTION_ID'],
					'ELEMENT_ID'=> $uniqueId,
					'USER_ID'=> $userId,
					'IS_DIR'=> (int)$data['isSection'],
				);

				if($data['isSection'] && !isset($sectionIds[$data['ID']]))
				{
					$sectionIds[$data['ID']] = $data['ID'];
				}
			}
			unset($ownerSection);

			unset(static::$dataDeletingMark[$key]);
		}
		unset($data);

		if($deleteInviteOnSection && $sectionIds)
		{
			\Bitrix\Webdav\FolderInviteTable::deleteByFilter(array(
				'=SECTION_ID' => $sectionIds,
			));
		}

		CWebDavLogDeletedElement::addBatch($keeper);
	}

	/**
	 * @see getallheaders()
	 * @return array|false
	 */
	protected function getAllHeaders()
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
}

class CWebDavBadStorageAfterMigrateException extends Exception
{}
class CWebDavAccessDeniedException extends Exception
{}
class CWebDavSymlinkMoveFakeErrorException extends Exception
{}