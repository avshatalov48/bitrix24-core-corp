<?php

use Bitrix\Disk\Sharing;
use Bitrix\Main\Data;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\FolderLink;
use Bitrix\Disk\Internals\ExternalLinkTable;
use Bitrix\Disk\Internals\FolderTable;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Type\DateTime;

if(!(\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::IncludeModule('disk')))
{
	return false;
}
class CDiskStorage extends CWebDavAbstractStorage
{
	private $cacheBreadcrumbs = array();
	private $errorCollection = array();

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * @param $storageId
	 * @return $this
	 */
	public function setStorageId($storageId)
	{
		$this->storageId = $storageId;
		$this->storage = \Bitrix\Disk\Storage::loadById($storageId['IBLOCK_ID'], array('ROOT_OBJECT'));

		return $this;
	}


	/** @var  \Bitrix\Disk\Storage */
	protected $storage;

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
		return 'CDiskStorage';
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
		$sc = $this->storage->getCurrentUserSecurityContext();
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

	private function loadFormattedFolderTreeAndBreadcrumbs($returnTree = false)
	{
		$cache = Data\Cache::createInstance();
		if($cache->initCache(15768000, 'storage_tr_' . $this->storage->getId(), 'disk'))
		{
			list($formattedFolders, $this->cacheBreadcrumbs) = $cache->getVars();
		}
		else
		{
			$querySharedFolders = \Bitrix\Disk\Sharing::getList(array(
				'filter' => array(
					'FROM_ENTITY' => Sharing::CODE_USER . $this->getUser()->getId(),
					'!TO_ENTITY' => Sharing::CODE_USER . $this->getUser()->getId(),
				),
			));
			$sharedFolders = array();
			while($sharedFolder = $querySharedFolders->fetch())
			{
				$sharedFolders[$sharedFolder['REAL_OBJECT_ID']] = $sharedFolder['REAL_OBJECT_ID'];
			}
			$formattedFolders = array();
			foreach($this->walkAndBuildTree($this->storage->getRootObject()) as $folder)
			{
				/** @var Folder $folder */
				$formattedFolders[] = $this->formatFolderToResponse($folder, isset($sharedFolders[$folder->getId()]));
			}
			unset($folder);

			$cache->startDataCache();
			$cache->endDataCache(array($formattedFolders, $this->cacheBreadcrumbs));
		}

		return $returnTree? $formattedFolders : null;
	}

	/**
	 * @param int $version
	 * @return array
	 */
	public function getSnapshot($version = 0)
	{
		$internalVersion = CWebDavDiskDispatcher::convertFromExternalVersion($version);
		$sc = $this->storage->getCurrentUserSecurityContext();

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
			elseif($internalVersion > 0 && self::compareVersion($folder['version'], $version) >= 0)
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
			$filter['>=UPDATE_TIME'] = DateTime::createFromTimestamp($internalVersion);
		}
		$code = Folder::CODE_FOR_UPLOADED_FILES;
		$parameters = array(
			'filter' => $filter,
		);
		$parameters['runtime'] = array(new ExpressionField('NOT_UPLOADED',
			"CASE WHEN NOT EXISTS(SELECT 'x' FROM b_disk_object_path pp INNER JOIN b_disk_object oo ON oo.ID = pp.PARENT_ID AND oo.CODE = '{$code}' WHERE pp.OBJECT_ID = %1\$s AND pp.PARENT_ID = oo.ID AND oo.STORAGE_ID = %2\$s) THEN 1 ELSE 0 END", array('PARENT_ID', 'STORAGE_ID'))
		);
		$parameters['filter']['NOT_UPLOADED'] = true;

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

		$sc = $this->storage->getCurrentUserSecurityContext();
		foreach($folderLinks as $link)
		{

			$modelLink = FolderLink::buildFromArray(array(
				'ID' => $link['extra']['id'],
				'NAME' => $link['name'],
				'TYPE' => ObjectTable::TYPE_FOLDER,
				'STORAGE_ID' => $link['extra']['iblockId'],
				'REAL_OBJECT_ID' => $link['extra']['linkSectionId'],
				'PARENT_ID' => $link['extra']['sectionId'],
				'UPDATE_TIME' => DateTime::createFromTimestamp(CWebDavDiskDispatcher::convertFromExternalVersion($link['version'])),
				'CREATED_BY' => $link['createdBy'],
				'UPDATED_BY' => $link['updatedBy'],
			));

			$filter = array(
				'TYPE' => ObjectTable::TYPE_FILE,
			);
			if($version > 0 && self::compareVersion($link['version'], $version .'000'))
			{
				$filter['>=UPDATE_TIME'] = DateTime::createFromTimestamp($version);
			}
			$code = Folder::CODE_FOR_UPLOADED_FILES;
			$parameters = array(
				'filter' => $filter,
			);
			$parameters['runtime'] = array(new ExpressionField('NOT_UPLOADED',
				"CASE WHEN NOT EXISTS(SELECT 'x' FROM b_disk_object_path pp INNER JOIN b_disk_object oo ON oo.ID = pp.PARENT_ID AND oo.CODE = '{$code}' WHERE pp.OBJECT_ID = %1\$s AND pp.PARENT_ID = oo.ID AND oo.STORAGE_ID = %2\$s) THEN 1 ELSE 0 END", array('PARENT_ID', 'STORAGE_ID'))
			);
			$parameters['filter']['NOT_UPLOADED'] = true;


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

		$q = \Bitrix\Disk\Internals\DeletedLogTable::getList(array(
			'filter' => array(
				'STORAGE_ID' => $this->storage->getId(),
				'>=CREATE_TIME' => DateTime::createFromTimestamp($version),
			),
			'order' => array('CREATE_TIME' => 'DESC'),
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
				'version' => CWebDavDiskDispatcher::convertToExternalVersion($row['CREATE_TIME']->getTimestamp()),
			);
		}

		return $deletedItems;
	}


	/**
	 * @param array $items
	 * @param int   $version
	 * @return Object[]
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
			if(self::compareVersion($item->getUpdateTime()->getTimestamp() . '000', $version) < 0)
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
		$this->loadFormattedFolderTreeAndBreadcrumbs();
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
		$this->loadFormattedFolderTreeAndBreadcrumbs();
		return $this->formatFolderToResponse($folder);
	}

	/**
	 * @param $file
	 * @return boolean
	 */
	public function sendFile($file)
	{
		/** @var File $file */
		$file = File::loadById($file['extra']['id']);
		if(!$file->canRead($this->storage->getCurrentUserSecurityContext()))
		{
			throw new CWebDavAccessDeniedException;
		}

		return CFile::viewByUser($file->getFile(), array("force_download" => true));
	}

	/**
	 * @param $name
	 * @param $parentDirectoryId
	 * @return array|boolean
	 */
	public function addDirectory($name, $parentDirectoryId)
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

		if(!$folder->canAdd($this->storage->getCurrentUserSecurityContext()))
		{
			throw new CWebDavAccessDeniedException;
		}

		$sub = $folder->addSubFolder(array(
			'NAME' => $name,
			'CREATED_BY' => $this->getUser()->getId(),
		));
		if($sub)
		{
			$this->loadFormattedFolderTreeAndBreadcrumbs();
			return $this->formatFolderToResponse($sub);
		}
		$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " , addSubFolder by name {$name}, parentId {$folder->getId()}", 199147)));
		$this->errorCollection->add($folder->getErrors());

		/** @var Folder $folder */
		$parentId = $folder->getRealObject()->getId();
		$folder = Folder::load(array('NAME' => $name, 'PARENT_ID' => $parentId));
		if($folder)
		{
			$this->loadFormattedFolderTreeAndBreadcrumbs();
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

		if(!$sourceFolder->canMove($this->storage->getCurrentUserSecurityContext(), $newParentFolder))
		{
			throw new CWebDavAccessDeniedException;
		}

		if($sourceFolder->moveTo($newParentFolder, $this->getUser()->getId()))
		{
			$this->loadFormattedFolderTreeAndBreadcrumbs();
			return $this->getDirectory(null, array('id' => $sourceFolder->getId()), true);
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

		if(!$sourceFile->canMove($this->storage->getCurrentUserSecurityContext(), $parentFolder))
		{
			throw new CWebDavAccessDeniedException;
		}

		if($sourceFile->moveTo($parentFolder, $this->getUser()->getId()))
		{
			$this->loadFormattedFolderTreeAndBreadcrumbs();
			return $this->getFile(null, array('id' => $sourceFile->getId()), true);
		}
		$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . ", moveTo to {$targetElementId}", 11151)));
		$this->errorCollection->add($sourceFile->getErrors());

		return array();
	}

	/**
	 * @param                $name
	 * @param                $targetDirectoryId
	 * @param CWebDavTmpFile $tmpFile
	 * @return array|boolean
	 */
	public function addFile($name, $targetDirectoryId, CWebDavTmpFile $tmpFile)
	{
		if(!$targetDirectoryId)
		{
			$folder = $this->storage->getRootObject();
		}
		else
		{
			$folder = Folder::loadById($targetDirectoryId);
		}

		if(!$folder)
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " by id {$targetDirectoryId}", 11152)));
			throw new WebDavStorageBreakDownException('bd addFile could not find folder');
		}

		if(!$folder->canAdd($this->storage->getCurrentUserSecurityContext()))
		{
			throw new CWebDavAccessDeniedException;
		}

		$fileArray = CFile::MakeFileArray($tmpFile->getAbsolutePath());
		$fileArray['name'] = $name;
		$fileModel = $folder->uploadFile($fileArray, array('NAME' => $name, 'CREATED_BY' => $this->getUser()->getId()));
		if($fileModel)
		{
			$this->loadFormattedFolderTreeAndBreadcrumbs();
			return $this->formatFileToResponse($fileModel);
		}
		$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . ", uploadFile to {$targetDirectoryId}", 11153)));
		$this->errorCollection->add($folder->getErrors());

		throw new WebDavStorageBreakDownException('bd addFile');
	}

	/**
	 * @param                $name
	 * @param                $targetElementId
	 * @param CWebDavTmpFile $tmpFile
	 * @return array|boolean
	 */
	public function updateFile($name, $targetElementId, CWebDavTmpFile $tmpFile)
	{
		/** @var File $file */
		$file = File::loadById($targetElementId);
		if(!$file)
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " by id {$targetElementId}", 11154)));
			return false;
		}

		if(!$file->canUpdate($this->storage->getCurrentUserSecurityContext()))
		{
			throw new CWebDavAccessDeniedException;
		}

		$fileArray = CFile::MakeFileArray($tmpFile->getAbsolutePath());
		if(!$fileArray)
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " MakeFileArray", 11155)));
			return false;
		}
		if($file->uploadVersion($fileArray, $this->getUser()->getId()))
		{
			$this->loadFormattedFolderTreeAndBreadcrumbs();
			return $this->formatFileToResponse($file);
		}

		$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . ", uploadVersion", 11156)));
		$this->errorCollection->add($file->getErrors());

		return false;
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

		if(!$file->canMarkDeleted($this->storage->getCurrentUserSecurityContext()))
		{
			throw new CWebDavAccessDeniedException;
		}

		if($file->markDeleted($this->getUser()->getId()))
		{
			return $this->getVersionDelete($fileArray);
		}
		$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . ", markDeleted", 11158)));
		$this->errorCollection->add($file->getErrors());

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

		if(!$folder->canMarkDeleted($this->storage->getCurrentUserSecurityContext()))
		{
			throw new CWebDavAccessDeniedException;
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
		$v = \Bitrix\Disk\Internals\DeletedLogTable::getList(array('filter' => array(
			'STORAGE_ID' => $this->storage->getId(),
			'OBJECT_ID' => $element['extra']['id'],
		),
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
		if(!$sourceFolder->canRename($this->storage->getCurrentUserSecurityContext()))
		{
			throw new CWebDavAccessDeniedException;
		}

		if($sourceFolder->rename($name, $this->getUser()->getId()))
		{
			$this->loadFormattedFolderTreeAndBreadcrumbs();
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
		if(!$sourceFile->canRename($this->storage->getCurrentUserSecurityContext()))
		{
			throw new CWebDavAccessDeniedException;
		}

		if($sourceFile->rename($name, $this->getUser()->getId()))
		{
			$this->loadFormattedFolderTreeAndBreadcrumbs();
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
		if(Path::validateFilename($name) && strpos($name, '%') === false)
		{
			return true;
		}
		$msg = 'File/Directory name should not have ' . Path::INVALID_FILENAME_CHARS . '%';

		return false;
	}

	public function getPublicLink(array $file)
	{
		/** @var File $file */
		$file = File::loadById($file['extra']['id']);
		if(!$file)
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . " by id {$file['extra']['id']}", 111606)));
			return '';
		}
		if(!$file->canRead($this->storage->getCurrentUserSecurityContext()))
		{
			throw new CWebDavAccessDeniedException;
		}

		$extLinks = $file->getExternalLinks(array(
			'filter' => array(
				'OBJECT_ID' => $file->getId(),
				'CREATED_BY' => $this->getUser()->getId(),
				'TYPE' => \Bitrix\Disk\Internals\ExternalLinkTable::TYPE_MANUAL,
				'IS_EXPIRED' => false,
			),
			'limit' => 1,
		));
		$extModel = array_pop($extLinks);
		if(!$extModel)
		{
			$extModel = $file->addExternalLink(array(
				'CREATED_BY' => $this->getUser()->getId(),
				'TYPE' => ExternalLinkTable::TYPE_MANUAL,
			));
		}
		if(!$extModel)
		{
			$this->errorCollection->add(array(new Error("Could not " . __METHOD__ . ", addExternalLink", 121606)));
			$this->errorCollection->add($file->getErrors());

			return '';
		}

		return \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getShortUrlExternalLink(array(
			'hash' => $extModel->getHash(),
			'action' => 'default',
		), true);
	}

	/**
	 * @return array|bool|\CAllUser|\CUser
	 */
	protected function getUser()
	{
		global $USER;
		return $USER;
	}

	private function getBreadcrumbs(BaseObject $object)
	{
		$parentId = $object->isLink()? $object->getParentId() : $object->getRealObject()->getParentId();
		$realId = $object->isLink()? $object->getId() : $object->getRealObject()->getId();
		if(isset($this->cacheBreadcrumbs[$parentId]))
		{
			if($object instanceof File)
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
				if($object->isLink())
				{
					if(!$object->getRealObject())
					{
						return null;
					}
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

		return $this->cacheBreadcrumbs[$realId];
	}

	protected function formatFolderToResponse(Folder $folder, $markIsShared = false)
	{
		if(empty($folder) || !$folder->getName())
		{
			return array();
		}

		$path = $this->getBreadcrumbs($folder);
		if(!$path)
		{
			return array();
		}

		$result = array(
			'id' => $this->generateId(array('FILE' => false, 'ID' => $folder->getId())),
			'isDirectory' => true,
			'isShared' => (bool)$markIsShared,
			'isSymlinkDirectory' => $folder instanceof \Bitrix\Disk\FolderLink,
			'isDeleted' => false,
			'storageId' => $this->getStringStorageId(),
			'path' => '/' . trim($path, '/'),
			'name' => (string)$folder->getName(),
			'version' => (string)$this->generateTimestamp($folder->getUpdateTime()->getTimestamp()),
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

	private function formatFileToResponse(File $file)
	{
		if(empty($file) || !$file->getName())
		{
			return array();
		}
		$path = $this->getBreadcrumbs($file);
		if(!$path)
		{
			return array();
		}

		$result = array(
			'id' => $this->generateId(array('FILE' => true, 'ID' => $file->getId())),
			'isDirectory' => false,
			'isDeleted' => false,
			'storageId' => $this->getStringStorageId(),
			'path' => '/' . trim($path, '/'),
			'name' => (string)$file->getName(),
			'revision' => $file->getFileId(),
			'version' => (string)$this->generateTimestamp($file->getUpdateTime()->getTimestamp()),
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

		return $result;
	}

	protected function generateTimestamp($date)
	{
		return CWebDavDiskDispatcher::convertToExternalVersion($date);
	}
}