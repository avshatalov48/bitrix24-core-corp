<?php

namespace Bitrix\Disk\Bitrix24Disk\Legacy;

use Bitrix\Disk\Bitrix24Disk\PageState;
use Bitrix\Disk\Bitrix24Disk\TmpFile;
use Bitrix\Disk\Configuration;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Path;
use Bitrix\Disk\Sharing;
use Bitrix\Disk\SpecificFolder;
use Bitrix\Disk\Storage;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\Ui;
use Bitrix\Disk\User;
use Bitrix\Main\Application;
use Bitrix\Main\Data;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\FolderLink;
use Bitrix\Disk\Internals\ExternalLinkTable;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Bitrix24Disk\Legacy\Exceptions\AccessDeniedException;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\IO;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use \Bitrix\Disk\Internals;
use Bitrix\Pull\Event;

class DiskStorage extends AbstractStorage
{
	const ERROR_CREATE_FORK_FILE = 'DS_F_22001';

	/** @var  Storage */
	protected $storage;
	/** @var int */
	protected $userId;

	/** @var bool */
	protected $isEnabledObjectLock = false;
	/** @var \Bitrix\Main\DB\Connection */
	protected $connection;
	private $cacheBreadcrumbs = array();
	private $errorCollection = array();
	/** @var array */
	protected $sharedData = array();
	/** @var bool */
	private $isLoadedSharedStatuses = false;
	/** @var callable[]  */
	private $shutdownTasks = [];

	public function __construct($user = null)
	{
		$this->errorCollection = new ErrorCollection;
		$this->isEnabledObjectLock = Configuration::isEnabledObjectLock();

		global $USER;
		$this->userId = User::resolveUserId($user?: $USER);
		$this->connection = Application::getConnection();

		Application::getInstance()->addBackgroundJob([$this, 'finalize']);
	}

	public function finalize()
	{
		foreach ($this->shutdownTasks as $task)
		{
			if (is_callable($task))
			{
				$task();
			}
		}
	}

	/**
	 * @param \callable $callable
	 *
	 * @return DiskStorage
	 */
	public function addShutdownTask($callable)
	{
		$this->shutdownTasks[] = $callable;

		return $this;
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function clearErrors()
	{
		$this->errorCollection->clear();
	}

	/**
	 * @param $storageId
	 * @return $this
	 */
	public function setStorageId($storageId)
	{
		$this->storageId = $storageId;
		$this->storage = Storage::loadById($storageId['IBLOCK_ID'], array('ROOT_OBJECT'));

		return $this;
	}

	/**
	 * @return \Bitrix\Disk\Storage
	 */
	public function getUserStorage()
	{
		return $this->storage;
	}

	/**
	 * @return string
	 */
	public function getStorageClassName()
	{
		return get_called_class();
	}

	public function parseStorageExtra(array $source)
	{
		return array(
			'iblockId' => empty($source['iblockId']) ? null : $source['iblockId'],
			'sectionId' => empty($source['sectionId']) ? null : $source['sectionId'],
		);
	}

	public function parseElementExtra(array $source)
	{
		return array(
			'id' => empty($source['id'])? null : (int)$source['id'],
			'iblockId' => empty($source['iblockId'])? null : (int)$source['iblockId'],
			'sectionId' => empty($source['sectionId'])? null : (int)$source['sectionId'],
			'rootSectionId' => empty($source['rootSectionId'])? null : (int)$source['rootSectionId'],
			'inSymlink' => empty($source['inSymlink'])? null : (int)$source['inSymlink'],
		);
	}

	/**
	 * @param array $element
	 * @return string
	 */
	public function generateId(array $element)
	{
		return implode('|', array(
			'st' . $this->getStringStorageId(), (empty($element['FILE'])? 's' : 'f') . $element['ID']
		));
	}

	private function walkAndBuildTree(Folder $rootFolder)
	{
		$sc = $this->storage->getSecurityContext($this->userId);
		$folders = array();
		foreach($rootFolder->getDescendants($sc,
			array('filter' => array('TYPE' => ObjectTable::TYPE_FOLDER))) as $item)
		{
			/** @var Folder $item */
			if($item->getCode() == Folder::CODE_FOR_UPLOADED_FILES)
			{
				continue;
			}

			$folders[] = $item;
			if($item->isLink())
			{
				if($item->getRealObjectId() == $rootFolder->getRealObjectId())
				{
					continue;
				}

				$folders = array_merge($folders, $this->walkAndBuildTree($item));
			}
		}
		unset($item);

		return $folders;
	}

	protected function flushTreeCache()
	{}

	protected function loadTree(): void
	{
		$this->loadFormattedFolderTreeAndBreadcrumbs();
	}

	/**
	 * Loads data which tells the object is shared or not by current user.
	 *
	 * @return void
	 */
	protected function loadSharedData()
	{
		if($this->isLoadedSharedStatuses)
		{
			return;
		}

		$cache = Data\Cache::createInstance();
		if($cache->initCache(15768000, 'storage_isshared_' . $this->storage->getId(), 'disk'))
		{
			[$this->sharedData,] = $cache->getVars();
		}
		else
		{
			$this->buildSharedData();

			$cache->startDataCache();
			$cache->endDataCache(array($this->sharedData,));
		}

		$this->isLoadedSharedStatuses = true;
	}

	protected function buildSharedData()
	{
		$storageId = $this->storage->getId();
		$rootObjectId = $this->storage->getRootObjectId();
		$fromEntity = $this->connection->getSqlHelper()->forSql(Sharing::CODE_USER . $this->storage->getEntityId());

		$query = new Internals\Entity\Query(Internals\SharingTable::getEntity());
		$query->addSelect('PATH.OBJECT_ID', 'OBJECT_ID');
		$query->registerRuntimeField('',
			new ReferenceField('PATH',
				Internals\ObjectPathTable::getEntity(),
				array('=this.REAL_OBJECT_ID' => 'ref.OBJECT_ID'),
				array('join_type' => 'INNER')
			)
		);
		$query->addFilter('REAL_STORAGE_ID', $storageId);
		$query->addFilter('PATH.PARENT_ID', $rootObjectId);
		$query->addFilter('=FROM_ENTITY', $fromEntity);
		$query->addFilter('!==TO_ENTITY', $fromEntity);

		foreach($query->exec() as $sharedRow)
		{
			$this->sharedData[$sharedRow['OBJECT_ID']] = true;
		}
	}

	protected function isSharedObject($objectId)
	{
		return isset($this->sharedData[$objectId]);
	}

	private function loadFormattedFolderTreeAndBreadcrumbs($returnTree = false)
	{
		$cache = Data\Cache::createInstance();
		if($cache->initCache(15768000, 'storage_tr_' . $this->storage->getId(), 'disk'))
		{
			[$formattedFolders, $this->cacheBreadcrumbs] = $cache->getVars();
		}
		else
		{
			$formattedFolders = array();
			foreach($this->walkAndBuildTree($this->storage->getRootObject()) as $folder)
			{
				/** @var Folder $folder */
				$formattedFolders[] = $this->formatFolderToResponse($folder);
			}
			unset($folder);

			$cache->startDataCache();
			$cache->endDataCache(array($formattedFolders, $this->cacheBreadcrumbs));
		}

		return $returnTree? $formattedFolders : null;
	}

	/**
	 * @param int            $version
	 * @param PageState|null $pageState
	 * @param PageState      $nextPageState
	 * @return array
	 */
	public function getSnapshot($version = 0, PageState $pageState = null, PageState &$nextPageState = null)
	{
		$internalVersion = $this->convertFromExternalVersion($version);
		$sc = $this->storage->getSecurityContext($this->userId);

		$response = $folderLinks = array();
		$folders = $this->loadFormattedFolderTreeAndBreadcrumbs(true);
		foreach($folders as $folder)
		{
			if(empty($folder))
			{
				continue;
			}
			if(!empty($folder['isSymlinkDirectory']))
			{
				$folderLinks[] = $folder;
			}

			if($internalVersion <= 0)
			{
				$response[] = $folder;
			}
			elseif($internalVersion > 0 && $this->compareVersion($folder['version'], $version) >= 0)
			{
				$response[] = $folder;
			}
		}
		unset($folder);

		$filter = array(
			'TYPE' => ObjectTable::TYPE_FILE,
		);
		if($internalVersion > 0)
		{
			$filter['>=SYNC_UPDATE_TIME'] = DateTime::createFromTimestamp($internalVersion);
		}
		$code = Folder::CODE_FOR_UPLOADED_FILES;
		$parameters = array(
			'filter' => $filter,
		);
		$parameters['runtime'] = array(new ExpressionField('NOT_UPLOADED',
			"CASE WHEN NOT EXISTS(SELECT 'x' FROM b_disk_object_path pp INNER JOIN b_disk_object oo ON oo.ID = pp.PARENT_ID AND oo.CODE = '{$code}' WHERE pp.OBJECT_ID = %1\$s AND pp.PARENT_ID = oo.ID AND oo.STORAGE_ID = %2\$s) THEN 1 ELSE 0 END", array('PARENT_ID', 'STORAGE_ID'))
		);
		$parameters['filter']['NOT_UPLOADED'] = true;

		if($this->isEnabledObjectLock)
		{
			$parameters['with'] = array('LOCK');
		}

		/**
		 * @var File $item
		 */
		foreach ($this
			->storage->getRootObject()
			->getDescendants($sc, $parameters) as $i => $item)
		{
			$format = $this->formatFileToResponse($item);
			if($format)
			{
				$response[] = $format;
			}
		}
		unset($item);

		return array_merge(
			$response,
			$this->getSnapshotFromLinks($folderLinks, $internalVersion),
			$this->getDeletedElements($internalVersion)
		);
	}

	protected function getSnapshotFromLinks(array $folderLinks, $version)
	{
		$response = array();

		$sc = $this->storage->getSecurityContext($this->userId);
		foreach($folderLinks as $link)
		{

			$modelLink = FolderLink::buildFromArray(array(
				'ID' => $link['extra']['id'],
				'NAME' => $link['name'],
				'TYPE' => ObjectTable::TYPE_FOLDER,
				'STORAGE_ID' => $link['extra']['iblockId'],
				'REAL_OBJECT_ID' => $link['extra']['linkSectionId'],
				'PARENT_ID' => $link['extra']['sectionId'],
				'UPDATE_TIME' => DateTime::createFromTimestamp($this->convertFromExternalVersion($link['originalTimestamp'])),
				'SYNC_UPDATE_TIME' => DateTime::createFromTimestamp($this->convertFromExternalVersion($link['version'])),
				'CREATED_BY' => $link['createdBy'],
				'UPDATED_BY' => $link['updatedBy'],
			));

			$filter = array(
				'TYPE' => ObjectTable::TYPE_FILE,
			);
			if($version > 0 && $this->compareVersion($link['version'], $version .'000') < 0)
			{
				$filter['>=SYNC_UPDATE_TIME'] = DateTime::createFromTimestamp($version);
			}
			$code = Folder::CODE_FOR_UPLOADED_FILES;
			$parameters = array(
				'filter' => $filter,
			);
			$parameters['runtime'] = array(new ExpressionField('NOT_UPLOADED',
				"CASE WHEN NOT EXISTS(SELECT 'x' FROM b_disk_object_path pp INNER JOIN b_disk_object oo ON oo.ID = pp.PARENT_ID AND oo.CODE = '{$code}' WHERE pp.OBJECT_ID = %1\$s AND pp.PARENT_ID = oo.ID AND oo.STORAGE_ID = %2\$s) THEN 1 ELSE 0 END", array('PARENT_ID', 'STORAGE_ID'))
			);
			$parameters['filter']['NOT_UPLOADED'] = true;

			if($this->isEnabledObjectLock)
			{
				$parameters['with'] = array('LOCK');
			}

			foreach($modelLink->getDescendants($sc, $parameters) as $item)
			{
				/** @var File $item */
				$format = $this->formatFileToResponse($item);
				if($format)
				{
					$response[] = $format;
				}
			}
			unset($item);
		}
		return $response;
	}

	protected function getDeletedElements($version)
	{
		$deletedItems = array();
		if($version <= 0)
		{
			return array();
		}
		//we have to flush to DB deleted log entries, which can be added on the hit.
		$deletedLogManager = Driver::getInstance()->getDeletedLogManager();
		$deletedLogManager->finalize();

		$q = $deletedLogManager->getEntries(array(
			'filter' => array(
				'STORAGE_ID' => $this->storage->getId(),
				'>=CREATE_TIME' => DateTime::createFromTimestamp($version),
			),
		));

		while($row = $q->fetch())
		{
			if(!$row)
			{
				continue;
			}
			$deletedItems[] = array(
				'id' => $this->generateId(array('FILE' => $row['TYPE'] == ObjectTable::TYPE_FILE, 'ID' => $row['OBJECT_ID'])),
				'isDirectory' => $row['TYPE'] == ObjectTable::TYPE_FOLDER,
				'deletedBy' => (string) (isset($row['USER_ID'])? $row['USER_ID'] : 0),
				'isDeleted' => true,
				'storageId' => $this->getStringStorageId(),
				'version' => $this->convertToExternalVersion($row['CREATE_TIME']->getTimestamp()),
			);
		}

		return $deletedItems;
	}


	/**
	 * @param array $items
	 * @param int   $version
	 * @return BaseObject[]
	 */
	protected function filterByVersion(array $items, $version = 0)
	{
		if($version == 0)
		{
			return $items;
		}

		/** @var \Bitrix\Disk\BaseObject $item */
		foreach ($items as $i => $item)
		{
			if($this->compareVersion($item->getSyncUpdateTime()->getTimestamp() . '000', $version) < 0)
			{
				unset($items[$i]);
			}
		}

		return $items;
	}

	/**
	 * @param       $id
	 * @param array $extra
	 * @param bool  $skipCheckId
	 * @return array|boolean
	 */
	public function getFile($id, array $extra, $skipCheckId = true)
	{
		if(!$skipCheckId && $this->generateId(array('ID' => $extra['id'], 'FILE' => true)) != $id)
		{
			return false;
		}
		$file = File::loadById($extra['id']);
		if(!$file)
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . "  by id {$extra['id']}", 11145)));
			return array();
		}
		$this->loadTree();
		return $this->formatFileToResponse($file);
	}

	/**
	 * @param       $id
	 * @param array $extra
	 * @param bool  $skipCheckId
	 * @return array|boolean
	 */
	public function getDirectory($id, array $extra, $skipCheckId = true)
	{
		if(!$skipCheckId && $this->generateId(array('ID' => $extra['id'], 'FILE' => true)) != $id)
		{
			return false;
		}
		/** @var Folder $folder */
		$folder = Folder::loadById($extra['id']);
		if(!$folder)
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . "  by id {$extra['id']}", 11146)));
			return array();
		}
		$this->loadTree();
		return $this->formatFolderToResponse($folder);
	}

	/**
	 * @param $file
	 * @throws AccessDeniedException
	 * @return bool|void
	 */
	public function sendFile($file)
	{
		/** @var File $file */
		$file = File::loadById($file['extra']['id']);
		if(!$file || !$file->canRead($this->storage->getSecurityContext($this->userId)))
		{
			throw new AccessDeniedException;
		}

		/** @noinspection PhpUndefinedClassInspection */
		/** @noinspection PhpVoidFunctionResultUsedInspection */
		return \CFile::viewByUser($file->getFile(), array("force_download" => true));
	}

	/**
	 * @param       $name
	 * @param       $parentDirectoryId
	 * @param array $data
	 * @return array|bool
	 * @throws AccessDeniedException
	 */
	public function addDirectory($name, $parentDirectoryId, array $data = array())
	{
		if(!$parentDirectoryId)
		{
			$folder = $this->storage->getRootObject();
		}
		else
		{
			$folder = Folder::loadById($parentDirectoryId);
		}

		if(!$folder)
		{
			$this->errorCollection->add(array(new Error("Could not find folder " . __METHOD__ . "  by  {$name}, {$parentDirectoryId}", 189146)));
			return array();
		}

		if(!$folder->canAdd($this->storage->getSecurityContext($this->userId)))
		{
			throw new AccessDeniedException;
		}

		$folderData = array(
			'NAME' => $name,
			'CREATED_BY' => $this->getUser()->getId(),
		);
		if(!empty($data['originalTimestamp']))
		{
			$folderData['UPDATE_TIME'] = DateTime::createFromTimestamp($this->convertFromExternalVersion($data['originalTimestamp']));
		}

		if(isset($data['code']) && $data['code'] === 'saved_files')
		{
			$sub = $this->storage->getFolderForSavedFiles();
		}
		else
		{
			$sub = $folder->addSubFolder($folderData);
		}

		if($sub)
		{
			$this->loadTree();
			return $this->formatFolderToResponse($sub);
		}
		$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " , addSubFolder by name {$name}, parentId {$folder->getId()}", 199147)));
		$this->errorCollection->add($folder->getErrors());

		/** @var Folder $folder */
		$parentId = $folder->getRealObject()->getId();
		$folder = Folder::load(array('=NAME' => $name, 'PARENT_ID' => $parentId));
		if($folder)
		{
			$this->loadTree();
			return $this->formatFolderToResponse($folder);
		}
		$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " , load Folder by name {$name}, parentId {$parentId}", 11147)));
		return array();
	}

	/**
	 * @param $name
	 * @param $targetDirectoryId
	 * @param $newParentDirectoryId
	 * @internal param $parentDirectoryId
	 * @return array|bool
	 */
	public function moveDirectory($name, $targetDirectoryId, $newParentDirectoryId)
	{
		if(!$newParentDirectoryId)
		{
			$newParentFolder = $this->storage->getRootObject();
		}
		else
		{
			$newParentFolder = Folder::loadById($newParentDirectoryId);
		}
		/** @var Folder $sourceFolder */
		$sourceFolder = Folder::loadById($targetDirectoryId);
		if(!$sourceFolder || !$newParentFolder)
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " by id {$targetDirectoryId}", 11148)));
			return false;
		}

		if(!$sourceFolder->canMove($this->storage->getSecurityContext($this->userId), $newParentFolder))
		{
			throw new AccessDeniedException;
		}

		$movedFolder = $sourceFolder->moveTo($newParentFolder, $this->getUser()->getId());
		if($movedFolder)
		{
			$this->flushTreeCache();
			$this->loadTree();
			return $this->getDirectory(null, array('id' => $movedFolder->getId()), true);
		}
		$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . ", moveTo to {$targetDirectoryId}", 11149)));
		$this->errorCollection->add($sourceFolder->getErrors());
		return array();
	}

	/**
	 * @param $name
	 * @param $targetElementId
	 * @param $newParentDirectoryId
	 * @internal param $parentDirectoryId
	 * @return array|bool
	 */
	public function moveFile($name, $targetElementId, $newParentDirectoryId)
	{
		if(!$newParentDirectoryId)
		{
			$parentFolder = $this->storage->getRootObject();
		}
		else
		{
			$parentFolder = Folder::loadById($newParentDirectoryId);
		}

		/** @var File $sourceFile */
		$sourceFile = File::loadById($targetElementId);
		if(!$sourceFile || !$parentFolder)
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " by id {$targetElementId}", 11150)));
			return false;
		}

		if(!$sourceFile->canMove($this->storage->getSecurityContext($this->userId), $parentFolder))
		{
			throw new AccessDeniedException;
		}

		$movedFile = $sourceFile->moveTo($parentFolder, $this->getUser()->getId());
		if($movedFile)
		{
			$this->loadTree();
			return $this->getFile(null, array('id' => $movedFile->getId()), true);
		}
		$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . ", moveTo to {$targetElementId}", 11151)));
		$this->errorCollection->add($sourceFile->getErrors());

		return array();
	}

	/**
	 * @param         $name
	 * @param         $targetDirectoryId
	 * @param TmpFile $tmpFile
	 * @param array   $data
	 * @return array
	 * @throws AccessDeniedException
	 */
	public function addFile($name, $targetDirectoryId, TmpFile $tmpFile, array $data = array())
	{
		$this->addShutdownTask(function() use($tmpFile){
			$tmpFile->delete();
		});

		if(!$targetDirectoryId)
		{
			$folder = $this->storage->getRootObject();
		}
		else
		{
			$folder = Folder::loadById($targetDirectoryId);
		}
		/** @var Folder $folder */
		if(!$folder)
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " by id {$targetDirectoryId}", 11152)));
			return array();
		}

		if(!$folder->canAdd($this->storage->getSecurityContext($this->userId)))
		{
			throw new AccessDeniedException;
		}

		/** @var array $fileArray */
		if($tmpFile->isCloud() && $tmpFile->getContentType())
		{

			$fileId = \CFile::saveFile(array(
				'name' => $tmpFile->getFilename(),
				'tmp_name' => $tmpFile->getAbsolutePath(),
				'type' => $tmpFile->getContentType(),
				'width' => $tmpFile->getWidth(),
				'height' => $tmpFile->getHeight(),
				'size' => $tmpFile->getSize(),
				'MODULE_ID' => Driver::INTERNAL_MODULE_ID,
			), Driver::INTERNAL_MODULE_ID, true, true);
			if(!$fileId)
			{
				$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . ", save cloud file", 111588)));

				return array();
			}

			$fileArray = \CFile::getFileArray($fileId);
			if(!$fileArray)
			{
				$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . ", getFileArray", 191588)));

				return array();
			}

			$fileData = array(
				'NAME' => $name,
				'FILE_ID' => $fileId,
				'SIZE' => !isset($data['SIZE'])? $fileArray['FILE_SIZE'] : $data['SIZE'],
				'CREATED_BY' => $this->getUser()->getId(),
			);
			if(!empty($data['originalTimestamp']))
			{
				$fileData['UPDATE_TIME'] = DateTime::createFromTimestamp($this->convertFromExternalVersion($data['originalTimestamp']));
			}
			$fileModel = $folder->addFile($fileData);
			if(!$fileModel)
			{
				\CFile::delete($fileId);
			}
		}
		else
		{
			$fileArray = \CFile::makeFileArray($tmpFile->getAbsolutePath());
			if(!$fileArray)
			{
				$this->errorCollection->addOne(new Error("Could not " . __METHOD__ . " MakeFileArray", 110155));

				return array();
			}

			$fileArray['name'] = $name;
			$fileData = array('NAME' => $name, 'CREATED_BY' => $this->getUser()->getId());
			if(!empty($data['originalTimestamp']))
			{
				$fileData['UPDATE_TIME'] = DateTime::createFromTimestamp($this->convertFromExternalVersion($data['originalTimestamp']));
			}

			$fileArray['no_rotate'] = true;
			$fileModel = $folder->uploadFile($fileArray, $fileData);
			//it's crutch. There is process of name correction in uploadFile but we don't want to do it.
			//And we don't want to add new parameters to manage it. In this case we restore name by renaming.
			if ($fileModel && $fileModel->getName() != $name)
			{
				$fileModel->rename($name);
			}
		}

		if($fileModel)
		{
			if (TypeFile::isImage($fileModel))
			{
				$this->processImageRotate($tmpFile, $fileModel);
			}

			$fileModel->changeEtag($tmpFile->getToken());
			$this->loadTree();

			$response = $this->formatFileToResponse($fileModel);
			Driver::getInstance()->sendEvent($this->userId, 'fileUploaded', $response);

			return $response;
		}
		$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . ", uploadFile to {$targetDirectoryId}", 11153)));
		$this->errorCollection->add($folder->getErrors());

		return array();
	}

	protected function processImageRotate(TmpFile $tmpFile, File $fileModel)
	{
		if (!$this->hasImageOrientation($tmpFile, $fileModel))
		{
			return;
		}

		$ds = DIRECTORY_SEPARATOR;
		$targetDir = \CTempFile::getDirectoryName(2, 'disk_rotation' . $ds . md5(uniqid('dr', true)));
		checkDirPath($targetDir);
		$tmpFilePath = IO\Path::normalize($targetDir) . $ds . uniqid('rotation', true);

		checkDirPath($tmpFilePath);
		if (copy($tmpFile->getAbsolutePath(), $tmpFilePath))
		{
			$this->addShutdownTask(function() use($fileModel, $tmpFilePath) {
				$fileData = \CFile::makeFileArray($tmpFilePath);
				if ($fileData && $fileModel->uploadVersion($fileData, $fileModel->getCreatedBy()))
				{
					$result = Internals\FileTable::changeSyncDateTime($fileModel->getId(), (new DateTime())->add('1 seconds'));
					if ($result->isSuccess() && Loader::includeModule('pull'))
					{
						Event::send();
					}
				}

			});
		}
	}

	protected function hasImageOrientation(TmpFile $tmpFile, File $fileModel): bool
	{
		if (!TypeFile::isImage($fileModel))
		{
			return false;
		}

		if (!$tmpFile->isCloud())
		{
			$imgSize = \CFile::GetImageSize($tmpFile->getAbsolutePath(), false, false);
			if (!$imgSize)
			{
				return false;
			}

			if ($imgSize[2] !== \IMAGETYPE_JPEG)
			{
				return false;
			}

			$exifData = \CFile::ExtractImageExif($tmpFile->getAbsolutePath());

			return isset($exifData['Orientation']);
		}

		return false;
	}

	/**
	 * @param         $name
	 * @param         $targetElementId
	 * @param TmpFile $tmpFile
	 * @param array   $data
	 * @return array|bool
	 * @throws AccessDeniedException
	 */
	public function updateFile($name, $targetElementId, TmpFile $tmpFile, array $data = array())
	{
		$this->addShutdownTask(function() use($tmpFile){
			$tmpFile->delete();
		});

		/** @var File $file */
		$file = File::loadById($targetElementId);
		if(!$file)
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " by id {$targetElementId}", 11154)));
			return false;
		}

		if(!$file->canUpdate($this->storage->getSecurityContext($this->userId)))
		{
			throw new AccessDeniedException;
		}

		/** @var array $fileArray */
		if($tmpFile->isCloud() && $tmpFile->getContentType())
		{

			$fileId = \CFile::saveFile(array(
				'name' => $tmpFile->getFilename(),
				'tmp_name' => $tmpFile->getAbsolutePath(),
				'type' => $tmpFile->getContentType(),
				'width' => $tmpFile->getWidth(),
				'height' => $tmpFile->getHeight(),
				'size' => $tmpFile->getSize(),
				'MODULE_ID' => Driver::INTERNAL_MODULE_ID,
			), Driver::INTERNAL_MODULE_ID, true, true);

			$fileArray = \CFile::getFileArray($fileId);
			if(!$fileArray)
			{
				$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " getFileArray", 1115541)));
				return false;
			}
			if(!empty($data['originalTimestamp']))
			{
				$fileArray['UPDATE_TIME'] = DateTime::createFromTimestamp($this->convertFromExternalVersion($data['originalTimestamp']));
			}
			if($file->addVersion($fileArray, $this->getUser()->getId()))
			{
				$file->changeEtag($tmpFile->getToken());
				$this->loadTree();

				$response = $this->formatFileToResponse($file);
				Driver::getInstance()->sendEvent($this->userId, 'fileUploaded', $response);

				return $response;
			}
			elseif($file->getErrorByCode($file::ERROR_EXCLUSIVE_LOCK))
			{
				$forkedFile = $this->processWithLockedFile($file, $fileArray, $fileId);
				if($forkedFile)
				{
					$this->errorCollection->clear();
					$this->errorCollection[] = new Error(
						'Created a new file.',
						self::ERROR_CREATE_FORK_FILE,
						$forkedFile
					);
				}
			}
			else
			{
				\CFile::delete($fileId);
			}
		}
		else
		{
			$fileArray = \CFile::makeFileArray($tmpFile->getAbsolutePath());
			if(!$fileArray)
			{
				$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " MakeFileArray", 11155)));
				return false;
			}
			if(!empty($data['originalTimestamp']))
			{
				$fileArray['UPDATE_TIME'] = DateTime::createFromTimestamp($this->convertFromExternalVersion($data['originalTimestamp']));
			}
			if($file->uploadVersion($fileArray, $this->getUser()->getId()))
			{
				$file->changeEtag($tmpFile->getToken());
				$this->loadTree();

				$response = $this->formatFileToResponse($file);
				Driver::getInstance()->sendEvent($this->userId, 'fileUploaded', $response);

				return $response;

			}
			elseif($file->getErrorByCode($file::ERROR_EXCLUSIVE_LOCK))
			{
				$forkedFile = $this->processWithLockedFile($file, $fileArray);
				if($forkedFile)
				{
					$this->errorCollection->clear();
					$this->errorCollection[] = new Error(
						'Created a new file.',
						self::ERROR_CREATE_FORK_FILE,
						$forkedFile
					);
				}
			}
		}

		$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . ", uploadVersion", 11156)));
		$this->errorCollection->add($file->getErrors());

		return false;
	}

	private function processWithLockedFile(File $file, $fileArray, $fileId = null)
	{
		$folderForSavedFiles = $this->storage->getFolderForSavedFiles();
		if(!$folderForSavedFiles)
		{
			$fileId && \CFile::delete($fileId);
			$this->errorCollection[] = new Error("Could not " . __METHOD__, 686111);
			$this->errorCollection->add($this->storage->getErrors());

			return null;
		}

		if($fileId)
		{
			$forkedFile = $folderForSavedFiles->addFile(array(
				'NAME' => $file->getName(),
				'FILE_ID' => $fileId,
				'SIZE' => $fileArray['size'],
				'CREATED_BY' => $this->getUser()->getId(),
			), array(), true);
		}
		else
		{
			$forkedFile = $folderForSavedFiles->uploadFile($fileArray, array(
				'NAME' => $file->getName(),
				'CREATED_BY' => $this->getUser()->getId(),
			), array(), true);
		}

		if(!$forkedFile)
		{
			$fileId && \CFile::delete($fileId);
			$this->errorCollection[] = new Error("Could not " . __METHOD__, 686112);
			$this->errorCollection->add($folderForSavedFiles->getErrors());

			return null;
		}

		return $forkedFile;
	}

	public function deleteFile($fileArray)
	{
		/** @var File $file */
		$file = File::loadById($fileArray['extra']['id']);

		if(!$file)
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " by id {$fileArray['extra']['id']}", 11157)));
			return false;
		}

		if(!$file->canMarkDeleted($this->storage->getSecurityContext($this->userId)))
		{
			throw new AccessDeniedException;
		}

		if ($this->belongsToUploaded($file))
		{
			//attempt to delete file, which was uploaded into special folder.
			//BDisk doesn't synchronize it and doesn't have to delete it.
			return false;
		}

		if($file->markDeleted($this->getUser()->getId()))
		{
			return $this->getVersionDelete($fileArray);
		}
		$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . ", markDeleted", 11158)));
		$this->errorCollection->add($file->getErrors());

		return false;
	}

	protected function belongsToUploaded(File $file)
	{
		$securityContext = Driver::getInstance()->getFakeSecurityContext();
		foreach ($file->getParents($securityContext) as $parent)
		{
			if(!$parent instanceof Folder)
			{
				continue;
			}

			if ($parent->getCode() === SpecificFolder::CODE_FOR_UPLOADED_FILES)
			{
				return true;
			}
		}

		return false;
	}

	public function deleteDirectory($directory)
	{
		/** @var Folder $folder */
		$folder = Folder::loadById($directory['extra']['id']);

		if(!$folder)
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " by id {$directory['extra']['id']}", 1115800)));
			return false;
		}

		if(!$folder->canMarkDeleted($this->storage->getSecurityContext($this->userId)))
		{
			throw new AccessDeniedException;
		}

		if($folder->markDeleted($this->getUser()->getId()))
		{
			return $this->getVersionDelete($directory);
		}
		$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . ", markDeleted", 11159)));
		$this->errorCollection->add($folder->getErrors());

		return false;
	}

	public function getVersionDelete($element)
	{
		if(empty($element) || !is_array($element))
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . ", empty element", 11160)));
			return false;
		}
		//we have to flush to DB deleted log entries, which can be added on the hit.
		$deletedLogManager = Driver::getInstance()->getDeletedLogManager();
		$deletedLogManager->finalize();

		$v = $deletedLogManager->getEntries(array(
				'filter' => array(
						'STORAGE_ID' => $this->storage->getId(),
						'OBJECT_ID' => $element['extra']['id'],
				),
				'limit' => 1,
				'order' => array('CREATE_TIME' => 'DESC')
		))->fetch();

		if($v)
		{
			return $v['CREATE_TIME']->getTimestamp();
		}
		$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . ", find deletedLog", 111601)));
		return false;
	}

	public function renameDirectory($name, $targetDirectoryId, $parentDirectoryId)
	{
		/** @var Folder $sourceFolder */
		$sourceFolder = Folder::loadById($targetDirectoryId);
		if(!$sourceFolder)
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " by id {$targetDirectoryId}", 111602)));
			return false;
		}
		if(!$sourceFolder->canRename($this->storage->getSecurityContext($this->userId)))
		{
			throw new AccessDeniedException;
		}

		if($sourceFolder->rename($name))
		{
			$this->flushTreeCache();
			$this->loadTree();
			return $this->getDirectory(null, array('id' => $sourceFolder->getId()), true);
		}
		$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . ", rename", 111603)));
		$this->errorCollection->add($sourceFolder->getErrors());

		return array();
	}

	public function renameFile($name, $targetElementId, $parentDirectoryId)
	{
		/** @var File $sourceFile */
		$sourceFile = File::loadById($targetElementId);
		if(!$sourceFile)
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " by id {$targetElementId}", 111604)));
			return false;
		}
		if(!$sourceFile->canRename($this->storage->getSecurityContext($this->userId)))
		{
			throw new AccessDeniedException;
		}

		if($sourceFile->rename($name))
		{
			$this->loadTree();
			return $this->getFile(null, array('id' => $sourceFile->getId()), true);
		}
		$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . ", rename", 111605)));
		$this->errorCollection->add($sourceFile->getErrors());

		return array();
	}

	public function isUnique($name, $targetDirectoryId, &$opponentId = null)
	{
		return BaseObject::isUniqueName($name, $targetDirectoryId, null, $opponentId);
	}

	public function isCorrectName($name, &$msg)
	{
		if(BaseObject::isValidValueForField('NAME', $name))
		{
			return true;
		}
		$msg = 'File/Directory name should not have ' . Path::INVALID_FILENAME_CHARS;

		return false;
	}

	public function getPublicLink(array $objectData)
	{
		if(!Configuration::isEnabledExternalLink())
		{
			$this->errorCollection[] = new Error(
				'External link is disabled',
				181556
			);

			return false;
		}

		/** @var File|Folder $object */
		$object = BaseObject::loadById($objectData['extra']['id']);
		if(!$object)
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " by id {$objectData['extra']['id']}", 111606)));
			return '';
		}
		if(!$object->canRead($this->storage->getSecurityContext($this->userId)))
		{
			throw new AccessDeniedException;
		}

		$extLinks = $object->getExternalLinks(array(
			'filter' => array(
				'OBJECT_ID' => $object->getId(),
				'CREATED_BY' => $this->getUser()->getId(),
				'TYPE' => ExternalLinkTable::TYPE_MANUAL,
				'IS_EXPIRED' => false,
			),
			'limit' => 1,
		));
		$extModel = array_pop($extLinks);
		if(!$extModel)
		{
			$extModel = $object->addExternalLink(array(
				'CREATED_BY' => $this->getUser()->getId(),
				'TYPE' => ExternalLinkTable::TYPE_MANUAL,
			));
		}
		if(!$extModel)
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . ", addExternalLink", 121606)));
			$this->errorCollection->add($object->getErrors());

			return '';
		}

		return Driver::getInstance()->getUrlManager()->getShortUrlExternalLink(array(
			'hash' => $extModel->getHash(),
			'action' => 'default',
		), true);
	}

	public function lockFile(array $file)
	{
		if(!Configuration::isEnabledObjectLock())
		{
			$this->errorCollection[] = new Error(
				'Lock is disabled',
				181551
			);

			return false;
		}

		/** @var File $file */
		$file = File::loadById($file['extra']['id']);
		if(!$file)
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " by id {$file['extra']['id']}", 181606)));
			return false;
		}

		if(!$file->canLock($this->storage->getSecurityContext($this->userId)))
		{
			throw new AccessDeniedException;
		}

		if(!$file->lock($this->getUser()->getId()))
		{
			$this->errorCollection[] = new Error(
				"Could not " . __METHOD__ . " by id {$file['extra']['id']}",
				181552
			);
			$this->errorCollection->add($file->getErrors());

			return false;
		}

		return true;
	}

	public function unlockFile(array $file)
	{
		if(!Configuration::isEnabledObjectLock())
		{
			$this->errorCollection[] = new Error(
				'Lock is disabled',
				181551
			);

			return false;
		}

		/** @var File $file */
		$file = File::loadById($file['extra']['id']);
		if(!$file)
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " by id {$file['extra']['id']}", 181696)));
			return false;
		}

		if(!$file->canUnlock($this->storage->getSecurityContext($this->userId)))
		{
			throw new AccessDeniedException;
		}

		if(!$file->unlock($this->getUser()->getId()))
		{
			$this->errorCollection[] = new Error(
				"Could not " . __METHOD__ . " by id {$file['extra']['id']}",
				181558
			);
			$this->errorCollection->add($file->getErrors());

			return false;
		}

		return true;
	}

	/**
	 * @return array|bool|\CUser
	 */
	protected function getUser()
	{
		global $USER;
		return $USER;
	}

	protected function getPathByObject(BaseObject $object)
	{
		return $this->getBreadcrumbs($object);
	}

	private function getBreadcrumbs(BaseObject $object)
	{
		$parentId = $object->isLink()? $object->getParentId() : $object->getRealObject()->getParentId();
		$realId = $object->isLink()? $object->getId() : $object->getRealObject()->getId();
		$isFile = $object instanceof File;
		if(isset($this->cacheBreadcrumbs[$parentId]))
		{
			if($isFile)
			{
				return $this->cacheBreadcrumbs[$parentId] . '/' . $object->getName();
			}
			$this->cacheBreadcrumbs[$realId] = $this->cacheBreadcrumbs[$parentId] . '/' . $object->getName();
			if($object->isLink())
			{
				$this->cacheBreadcrumbs[$object->getRealObject()->getId()] = $this->cacheBreadcrumbs[$realId];
			}
		}
		else
		{
			if($parentId == $this->storage->getRootObjectId())
			{
				$this->cacheBreadcrumbs[$realId] = '/' . $object->getName();
				if($object->isLink() && $object->getRealObject())
				{
					$this->cacheBreadcrumbs[$object->getRealObject()->getId()] = $this->cacheBreadcrumbs[$realId];
				}
				return $this->cacheBreadcrumbs[$realId];
			}

			$path = '';
			$parents = ObjectTable::getAncestors($realId, array('select' => array('ID', 'NAME', 'TYPE', 'CODE')));
			while($parent = $parents->fetch())
			{
				if($parent['CODE'] == Folder::CODE_FOR_UPLOADED_FILES)
				{
					//todo hack. CODE_FOR_UPLOADED_FILES
					return null;
				}
				if($this->storage->getRootObjectId() == $parent['ID'])
				{
					continue;
				}
				$path .= '/' . $parent['NAME'];
				if(!isset($this->cacheBreadcrumbs[$parent['ID']]))
				{
					$this->cacheBreadcrumbs[$parent['ID']] = $path;
				}
			}
			if(isset($this->cacheBreadcrumbs[$parentId]))
			{
				$this->cacheBreadcrumbs[$realId] = $this->cacheBreadcrumbs[$parentId];
				if($object->isLink())
				{
					$this->cacheBreadcrumbs[$object->getRealObject()->getId()] = $this->cacheBreadcrumbs[$realId];
				}
			}
			else
			{
				$this->cacheBreadcrumbs[$realId] = null;
			}
		}

		return $isFile? $this->cacheBreadcrumbs[$realId]  . '/' . $object->getName() : $this->cacheBreadcrumbs[$realId];
	}

	private function getUpdateDateTimeFromObject(BaseObject $object)
	{
		$dateTime = $object->getUpdateTime()?: $object->getCreateTime();
		if ($dateTime)
		{
			return $dateTime;
		}

		return $object->getSyncUpdateTime();
	}

	protected function formatFolderToResponse(Folder $folder)
	{
		if(empty($folder) || $folder->getName() == '')
		{
			return array();
		}

		$path = $this->getPathByObject($folder);
		if(!$path)
		{
			return array();
		}

		$this->loadSharedData();

		$dateTime = $this->getUpdateDateTimeFromObject($folder);
		$result = array(
			'id' => $this->generateId(array('FILE' => false, 'ID' => $folder->getId())),
			'isDirectory' => true,
			'isShared' => $this->isSharedObject($folder->getId()),
			'isSymlinkDirectory' => $folder instanceof FolderLink,
			'isDeleted' => $folder->isDeleted(),
			'storageId' => $this->getStringStorageId(),
			'path' => '/' . trim($path, '/'),
			'name' => (string)$folder->getName(),
			'version' => (string)$this->generateTimestamp($folder->getSyncUpdateTime()->getTimestamp()),
			'originalTimestamp' => (string)$this->generateTimestamp($dateTime->getTimestamp()),
			'extra' => array(
				'id' => (string)$folder->getId(),
				'iblockId' => (string)$folder->getStorageId(),
				'sectionId' => (string)$folder->getParentId(),
				'linkSectionId' => (string)($folder->isLink()? $folder->getRealObjectId() : ''),
				'rootSectionId' => (string)$this->storage->getRootObjectId(),
				'name' => (string)$folder->getName(),
			),
			'permission' => 'W',
			'createdBy' => (string)$folder->getCreatedBy(),
			'modifiedBy' => (string)$folder->getUpdatedBy(),
		);
		if($this->storage->getRootObjectId() != $folder->getParentId())
		{
			$result['parentId'] = $this->generateId(array('FILE' => false, 'ID' => $folder->getParentId()));
		}

		return $result;
	}

	protected function formatFileToResponse(File $file)
	{
		if(empty($file) || $file->getName() == '')
		{
			return array();
		}

		$path = $this->getPathByObject($file);
		if(!$path)
		{
			return array();
		}

		$this->loadSharedData();

		$dateTime = $this->getUpdateDateTimeFromObject($file);
		$result = array(
			'id' => $this->generateId(array('FILE' => true, 'ID' => $file->getId())),
			'isDirectory' => false,
			'isShared' => $this->isSharedObject($file->getId()),
			'isSymlinkFile' => $file->isLink(),
			'isDeleted' => $file->isDeleted(),
			'storageId' => $this->getStringStorageId(),
			'path' => '/' . trim($path, '/'),
			'name' => (string)$file->getName(),
			'revision' => $file->getFileId(),
			'etag' => $file->getEtag()?: null,
			'version' => (string)$this->generateTimestamp($file->getSyncUpdateTime()->getTimestamp()),
			'originalTimestamp' => (string)$this->generateTimestamp($dateTime->getTimestamp()),
			'extra' => array(
				'id' => (string)$file->getId(),
				'iblockId' => (string)$file->getStorageId(),
				'sectionId' => (string)$file->getParentId(),
				'rootSectionId' => (string)$this->storage->getRootObjectId(),
				'name' => (string)$file->getName(),
			),
			'size' => (string)$file->getSize(),
			'permission' => 'W',
			'createdBy' => (string)$file->getCreatedBy(),
			'modifiedBy' => (string)$file->getUpdatedBy(),
		);
		if($this->storage->getRootObjectId() != $file->getParentId())
		{
			$result['parentId'] = $this->generateId(array('FILE' => false, 'ID' => $file->getParentId()));
		}

		if($this->isEnabledObjectLock)
		{
			$lock = $file->getLock();
			if($lock)
			{
				$result['lock'] = array(
					'createdBy' => (string)$lock->getCreatedBy(),
					'createTimestamp' => (string)$this->generateTimestamp($lock->getCreateTime()->getTimestamp()),
					'canUnlock' => $lock->canUnlock($this->getUser()->getId()),
				);
			}
		}

		return $result;
	}

	protected function generateTimestamp($date)
	{
		return $this->convertToExternalVersion($date);
	}

	public function convertFromExternalVersion($version)
	{
		if(mb_substr($version, -3, 3) === '000')
		{
			return mb_substr($version, 0, -3);
		}
		return $version;
	}

	public function convertToExternalVersion($version)
	{
		return ((string)$version) . '000';
	}
}
