<?php

namespace Bitrix\Disk;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\FileTable;
use Bitrix\Disk\Internals\FolderTable;
use Bitrix\Disk\Internals\ObjectNameService;
use Bitrix\Disk\Internals\ObjectPathTable;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\RightTable;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Disk\Internals\SimpleRightTable;
use Bitrix\Disk\Internals\VersionTable;
use Bitrix\Disk\ProxyType\Group;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Event;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Collection;
use Bitrix\Main\Type\DateTime;
use CFile;

Loc::loadMessages(__FILE__);

class Folder extends BaseObject
{
	const ERROR_COULD_NOT_DELETE_WITH_CODE = 'DISK_FOLDER_22001';
	const ERROR_COULD_NOT_SAVE_FILE        = 'DISK_FOLDER_22002';

	const CODE_FOR_CREATED_FILES  = SpecificFolder::CODE_FOR_CREATED_FILES;
	const CODE_FOR_SAVED_FILES    = SpecificFolder::CODE_FOR_SAVED_FILES;
	const CODE_FOR_UPLOADED_FILES = SpecificFolder::CODE_FOR_UPLOADED_FILES;

	/** @var bool */
	protected $hasSubFolders;

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getTableClassName()
	{
		return FolderTable::className();
	}

	/**
	 * Tells if the folder has sub-folders.
	 * Property {hasSubFolders} fills by using field HAS_SUBFOLDERS in select.
	 * @return boolean
	 */
	public function hasSubFolders()
	{
		return (bool)$this->hasSubFolders;
	}

	/**
	 * Checks rights to add object to current object.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canAdd(SecurityContext $securityContext)
	{
		return $securityContext->canAdd($this->id);
	}

	/**
	 * Pre-loads all operations for children.
	 * @internal
	 * @param SecurityContext $securityContext Security context.
	 */
	public function preloadOperationsForChildren(SecurityContext $securityContext)
	{
		$securityContext->preloadOperationsForChildren($this->id);
	}

	public function preloadOperationsForSpecifiedObjects(array $ids, SecurityContext $securityContext)
	{
		if (!$ids)
		{
			return;
		}

		$securityContext->preloadOperationsForSpecifiedObjects($this->id, $ids);
	}

	/**
	 * Adds row to entity table, fills error collection and builds model.
	 * @param array           $data Data.
	 * @param ErrorCollection $errorCollection Error collection.
	 * @return \Bitrix\Disk\Internals\Model|static|null
	 * @throws \Bitrix\Main\NotImplementedException
	 * @internal
	 */
	public static function add(array $data, ErrorCollection $errorCollection)
	{
		/** @var Folder $folder */
		$folder = parent::add($data, $errorCollection);
		if($folder && !$folder->isDeleted())
		{
			$driver = Driver::getInstance();
			$driver->getIndexManager()->indexFolder($folder);
			$driver->sendChangeStatusToSubscribers($folder);
		}

		return $folder;
	}

	/**
	 * Returns once model by specific filter.
	 * @param array $filter Filter.
	 * @param array $with List of eager loading.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return static
	 */
	public static function load(array $filter, array $with = array())
	{
		$filter['TYPE'] = ObjectTable::TYPE_FOLDER;

		return parent::load($filter, $with);
	}

	protected static function getClassNameModel(array $row)
	{
		$classNameModel = parent::getClassNameModel($row);
		if(
			$classNameModel === static::className() ||
			is_subclass_of($classNameModel, static::className()) ||
			in_array(static::className(), class_parents($classNameModel)) //5.3.9
		)
		{
			return $classNameModel;
		}

		throw new ObjectException('Could not to get non subclass of ' . static::className());
	}

	/**
	 * Tells if folder is root. It means folder does not have parent folder.
	 *
	 * @return bool
	 */
	public function isRoot()
	{
		return !$this->parentId;
	}

	/**
	 * Uploads new file to folder.
	 * @param array $fileArray Structure like $_FILES.
	 * @param array $data Contains additional fields (CREATED_BY, NAME, etc).
	 * @param array $rights Rights (@see \Bitrix\Disk\RightsManager).
	 * @param bool  $generateUniqueName Generates unique name for object in directory.
	 * @throws \Bitrix\Main\ArgumentException
	 * @return File|null
	 */
	public function uploadFile(array $fileArray, array $data, array $rights = array(), $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		$data['NAME'] = $this->resolveFileName($fileArray, $data);

		static::checkRequiredInputParams($data, array(
			'NAME', 'CREATED_BY'
		));

		if(!isset($fileArray['MODULE_ID']))
		{
			$fileArray['MODULE_ID'] = Driver::INTERNAL_MODULE_ID;
		}

		if(empty($fileArray['type']))
		{
			$fileArray['type'] = '';
		}

		$fileArray['type'] = TypeFile::normalizeMimeType($fileArray['type'], $data['NAME']);
		$fileArray['name'] = $data['NAME'];

		$fileId = CFile::saveFile($fileArray, Driver::INTERNAL_MODULE_ID, true, true);
		if(!$fileId)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage("DISK_FOLDER_MODEL_ERROR_COULD_NOT_SAVE_FILE"), self::ERROR_COULD_NOT_SAVE_FILE)));
			return null;
		}
		/** @var array $fileArray */

		$fileArray = CFile::getFileArray($fileId);

		$data['NAME'] = Ui\Text::correctFilename($data['NAME']);
		$fileModel = $this->addFile(array(
			'NAME' => $data['NAME'],
			'FILE_ID' => $fileId,
			'PREVIEW_ID' => isset($data['PREVIEW_ID'])? $data['PREVIEW_ID'] : null,
			'CONTENT_PROVIDER' => isset($data['CONTENT_PROVIDER'])? $data['CONTENT_PROVIDER'] : null,
			'SIZE' => !isset($data['SIZE'])? $fileArray['FILE_SIZE'] : $data['SIZE'],
			'CREATED_BY' => $data['CREATED_BY'],
			'UPDATE_TIME' => isset($data['UPDATE_TIME'])? $data['UPDATE_TIME'] : null,
			'CODE' => isset($data['CODE'])? $data['CODE'] : null,
		), $rights, $generateUniqueName);

		if(!$fileModel)
		{
			CFile::delete($fileId);
			return null;
		}
		return $fileModel;
	}

	private function resolveFileName(array $fileArray, array $data)
	{
		if (!empty($data['NAME']))
		{
			return $data['NAME'];
		}

		if (!empty($fileArray['name']))
		{
			return $fileArray['name'];
		}

		return null;
	}

	/**
	 * Creates blank file (size 0 byte) in folder.
	 * @param array $data Contains additional fields (CREATED_BY, NAME, MIME_TYPE).
	 * @param array $rights Rights (@see \Bitrix\Disk\RightsManager).
	 * @param bool  $generateUniqueName Generates unique name for object in directory.
	 * @return File|null
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function addBlankFile(array $data, array $rights = array(), $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		static::checkRequiredInputParams($data, array(
			'NAME', 'CREATED_BY', 'MIME_TYPE'
		));

		return $this->uploadFile(array(
			'name' => $data['NAME'],
			'content' => '',
			'type' => $data['MIME_TYPE'],
		), array(
			'NAME' => $data['NAME'],
			'SIZE' => isset($data['SIZE'])? $data['SIZE'] : null, //for im. We should show future!
			'CREATED_BY' => $data['CREATED_BY'],
		), $rights, $generateUniqueName);
	}

	/**
	 * Adds file in folder.
	 * @param array $data Contains additional fields (CREATED_BY, NAME, etc).
	 * @param array $rights Rights (@see \Bitrix\Disk\RightsManager).
	 * @param bool  $generateUniqueName Generates unique name for object in directory.
	 * @throws \Bitrix\Main\ArgumentException
	 * @return null|static|File
	 */
	public function addFile(array $data, array $rights = array(), $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		static::checkRequiredInputParams($data, array(
			'NAME'
		));

		$nameService = new ObjectNameService($data['NAME'], $this->id, ObjectTable::TYPE_FILE);
		if ($generateUniqueName)
		{
			$nameService->requireUniqueName();
		}

		$result = $nameService->prepareName();
		if (!$result->isSuccess())
		{
			$this->errorCollection->add($result->getErrors());

			return null;
		}

		$data['NAME'] = $result->getName();
		$data['PARENT_ID'] = $this->id;
		$data['STORAGE_ID'] = $this->storageId;
		$data['PARENT'] = $this;

		/** @var File $fileModel */
		$fileModel = $this->processAdd($data, $this->errorCollection, $generateUniqueName);
		if ($fileModel)
		{
			Driver::getInstance()->getRightsManager()->setAsNewLeaf($fileModel, $rights);

			$objectEvent = $this->makeObjectEvent(
				'objectAdded',
				[
					'object' => [
						'id' => (int)$this->getId(),
						'type' => FileTable::TYPE,
						'name' => $this->getName(),
					],
					'addedObject' => [
						'id' => (int)$fileModel->getId(),
					],
				]
			);
			$objectEvent->sendToObjectChannel();
		}

		return $fileModel;
	}

	private function isDuplicateKeyError(SqlQueryException $exception)
	{
		return mb_strpos($exception->getDatabaseMessage(), '(1062)') !== false;
	}

	private function processAdd(array $data, ErrorCollection $errorCollection, bool $generateUniqueName = false, int $countStepsToGenerateName = 0): File|null
	{
		try
		{
			$fileModel = File::add($data, $errorCollection);
			if (!$fileModel)
			{
				return null;
			}
		}
		catch (SqlQueryException $exception)
		{
			if ($generateUniqueName && $this->isDuplicateKeyError($exception))
			{
				$countStepsToGenerateName++;
				if ($countStepsToGenerateName > 10)
				{
					throw new InvalidOperationException(
						"Too many attempts ({$countStepsToGenerateName}) to generate unique name {$data['NAME']}"
					);
				}

				$nameService = new ObjectNameService($data['NAME'], $this->id, ObjectTable::TYPE_FILE);
				$nameService->requireUniqueName();

				$result = $nameService->prepareName();
				if ($result->isSuccess())
				{
					$data['NAME'] = $result->getName();
				}

				return $this->processAdd(
					$data,
					$errorCollection,
					$generateUniqueName,
					$countStepsToGenerateName
				);
			}

			throw $exception;
		}

		return $fileModel;
	}

	/**
	 * Adds link on file in folder.
	 * @param File  $sourceFile Source file.
	 * @param array $data Contains additional fields (CREATED_BY, NAME, etc).
	 * @param array $rights Rights (@see \Bitrix\Disk\RightsManager).
	 * @param bool  $generateUniqueName Generates unique name for object in directory.
	 * @throws \Bitrix\Main\ArgumentException
	 * @return File|null
	 */
	public function addFileLink(File $sourceFile, array $data, array $rights = array(), $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		$data = $this->prepareDataForAddLink($sourceFile, $data, $generateUniqueName);
		if(!$data)
		{
			return null;
		}
		$data['SIZE'] = $sourceFile->getSize();

		/** @var FileLink $fileLinkModel */
		$fileLinkModel = FileLink::add($data, $this->errorCollection);
		if(!$fileLinkModel)
		{
			return null;
		}

		$driver = Driver::getInstance();
		$driver->getRightsManager()->setAsNewLeaf($fileLinkModel, $rights);

		if (!$fileLinkModel->isDeleted())
		{
			$driver->getIndexManager()->indexFile($fileLinkModel);

//			$this->notifySonetGroup($fileLinkModel);
		}

		$objectEvent = $this->makeObjectEvent(
			'objectAdded',
			[
				'object' => [
					'id' => (int)$this->getId(),
					'type' => FileTable::TYPE,
					'name' => $this->getName(),
				],
				'addedObject' => [
					'id' => (int)$fileLinkModel->getId(),
				],
			]
		);
		$objectEvent->sendToObjectChannel();


		return $fileLinkModel;
	}

	private function notifySonetGroup(File $fileModel)
	{
		static 	$folderUrlList = array();

		//todo create NotifyManager, which provides notify (not only group)
		if(!Loader::includeModule('socialnetwork'))
		{
			return;
		}

		$storage = $fileModel->getStorage();
		$proxyType = $storage->getProxyType();
		if(!$proxyType instanceof Group)
		{
			return;
		}

		$groupId = (int)$storage->getEntityId();
		if($groupId <= 0)
		{
			return;
		}

		$urlManager = Driver::getInstance()->getUrlManager();
		$fileUrl = $urlManager->getPathFileDetail($fileModel);
		$fileCreatedBy = $fileModel->getCreatedBy();
		$fileName = $fileModel->getName();
		$storageId = $storage->getId();

		$res = ObjectTable::getList(array(
			'filter' => array(
				'STORAGE_ID' => $storageId,
				'CREATED_BY' => $fileCreatedBy,
				'>UPDATE_TIME' => DateTime::createFromTimestamp(time() - 600), // for index
				'>CREATE_TIME' => DateTime::createFromTimestamp(time() - 600),
				'=TYPE' => ObjectTable::TYPE_FILE,
				'!=FILE_ID' => $fileModel->getFileId()
			),
			'select' => array('ID'),
			'limit' => 1
		));

		if (!($res->fetch()))
		{
			$message = Loc::getMessage('DISK_FOLDER_MODEL_IM_NEW_FILE', array(
				'#TITLE#' => '<a href="#URL#" class="bx-notifier-item-action">'.$fileName.'</a>',
			));
			$messageOut = Loc::getMessage('DISK_FOLDER_MODEL_IM_NEW_FILE', array(
					'#TITLE#' => $fileName
				)).' (#URL#)';

			$url = $urlManager->encodeUrn($fileUrl);
		}
		else
		{
			$message = Loc::getMessage('DISK_FOLDER_MODEL_IM_NEW_FILE2', array(
				'#LINK_FOLDER_START#' => '<a href="#URL#" class="bx-notifier-item-action">',
				'#LINK_END#' => '</a>'
			));
			$messageOut = Loc::getMessage('DISK_FOLDER_MODEL_IM_NEW_FILE2', array(
					'#LINK_FOLDER_START#' => '',
					'#LINK_END#' => ''
				)).' (#URL#)';

			if (!array_key_exists($storageId, $folderUrlList))
			{
				$folderUrlList[$storageId] = $proxyType->getBaseUrlFolderList();
			}

			$url = $folderUrlList[$storageId];
		}


		\CSocNetSubscription::notifyGroup(array(
			'LOG_ID' => false,
			'GROUP_ID' => array($groupId),
			'NOTIFY_MESSAGE' => '',
			'NOTIFY_TAG' => 'DISK_GROUP|'.$groupId.'|'.$fileCreatedBy,
			'FROM_USER_ID' => $fileCreatedBy,
			'URL' => $url,
			'MESSAGE' => $message,
			'MESSAGE_OUT' => $messageOut,
			'EXCLUDE_USERS' => array($fileCreatedBy)
		));
	}

	/**
	 * Adds sub-folder in folder.
	 * @param array $data Contains additional fields (CREATED_BY, NAME, etc).
	 * @param array $rights Rights (@see \Bitrix\Disk\RightsManager).
	 * @param bool  $generateUniqueName Generates unique name for object in directory.
	 * @throws \Bitrix\Main\ArgumentException
	 * @return null|static|Folder
	 */
	public function addSubFolder(array $data, array $rights = array(), bool $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		static::checkRequiredInputParams($data, array(
			'NAME'
		));

		$nameService = new ObjectNameService($data['NAME'], $this->id, ObjectTable::TYPE_FOLDER);
		if ($generateUniqueName)
		{
			$nameService->requireUniqueName();
		}

		$result = $nameService->prepareName();
		if (!$result->isSuccess())
		{
			$this->errorCollection->add($result->getErrors());

			return null;
		}

		$data['NAME'] = $result->getName();
		$data['PARENT_ID'] = $this->id;
		$data['STORAGE_ID'] = $this->storageId;

		$folderModel = Folder::add($data, $this->errorCollection);
		if(!$folderModel)
		{
			return null;
		}
		$this->changeSelfUpdateTime();
		Driver::getInstance()->getRightsManager()->setAsNewLeaf($folderModel, $rights);

		$objectEvent = $this->makeObjectEvent(
			'objectAdded',
			[
				'object' => [
					'id' => (int)$this->getId(),
					'type' => FolderTable::TYPE,
					'name' => $this->getName(),
				],
				'addedObject' => [
					'id' => (int)$folderModel->getId(),
				],
			]
		);
		$objectEvent->sendToObjectChannel();

		return $folderModel;
	}

	/**
	 * Adds link on folder in folder.
	 * @param Folder $sourceFolder Original folder.
	 * @param array $data Contains additional fields (CREATED_BY, NAME, etc).
	 * @param array $rights Rights (@see \Bitrix\Disk\RightsManager).
	 * @param bool  $generateUniqueName Generates unique name for object in directory.
	 * @throws \Bitrix\Main\ArgumentException
	 * @return FolderLink|null
	 */
	public function addSubFolderLink(Folder $sourceFolder, array $data, array $rights = array(), $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		$data = $this->prepareDataForAddLink($sourceFolder, $data, $generateUniqueName);
		if(!$data)
		{
			return null;
		}

		/** @var FolderLink $fileLinkModel */
		$fileLinkModel = FolderLink::add($data, $this->errorCollection);
		if(!$fileLinkModel)
		{
			return null;
		}
		$this->changeSelfUpdateTime();
		Driver::getInstance()->getRightsManager()->setAsNewLeaf($fileLinkModel, $rights);

		$objectEvent = $this->makeObjectEvent(
			'objectAdded',
			[
				'object' => [
					'id' => (int)$this->getId(),
					'type' => FolderTable::TYPE,
					'name' => $this->getName(),
				],
				'addedObject' => [
					'id' => (int)$fileLinkModel->getId(),
				],
			]
		);
		$objectEvent->sendToObjectChannel();

		return $fileLinkModel;
	}

	private function prepareDataForAddLink(BaseObject $object, array $data, $generateUniqueName = false)
	{
		if (empty($data['NAME']))
		{
			$data['NAME'] = $object->getName();
		}

		static::checkRequiredInputParams($data, array(
			'NAME'
		));

		$nameService = new ObjectNameService($data['NAME'], $this->id, $object->getType());
		if ($generateUniqueName)
		{
			$nameService->requireUniqueName();
		}

		$result = $nameService->prepareName();
		if (!$result->isSuccess())
		{
			$this->errorCollection->add($result->getErrors());

			return null;
		}

		$data['NAME'] = $result->getName();

		if (isset($data['DELETED_TYPE']) && $data['DELETED_TYPE'] == ObjectTable::DELETED_TYPE_ROOT)
		{
			$data['NAME'] = Ui\Text::appendTrashCanSuffix($data['NAME']);
			if (!static::isUniqueName($data['NAME'], $this->id))
			{
				$this->errorCollection[] = new Error(Loc::getMessage('DISK_FOLDER_MODEL_ERROR_NON_UNIQUE_NAME'), self::ERROR_NON_UNIQUE_NAME);

				return null;
			}
		}


		$data['PARENT_ID'] = $this->id;
		$data['STORAGE_ID'] = $this->storageId;
		$data['REAL_OBJECT_ID'] = $object->getRealObject()->getId();

		return $data;
	}

	/**
	 * Copies object to target folder.
	 * @param Folder $targetFolder Target folder.
	 * @param int    $updatedBy Id of user.
	 * @param bool   $generateUniqueName Generates unique name for object in directory.
	 * @return BaseObject|null
	 */
	public function copyTo(Folder $targetFolder, $updatedBy, $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		if($this->getId() == $targetFolder->getId())
		{
			return $this;
		}

		$newRoot = $this->copyToInternal($targetFolder, $updatedBy, $generateUniqueName);
		if(!$newRoot)
		{
			return null;
		}
		$mapParentToNewParent = array(
			$this->getId() => $newRoot,
		);

		$parameters = array(
			'select' => array(
				'*',
				'DEPTH_LEVEL' => 'PATH_CHILD.DEPTH_LEVEL',
			),
			'filter' => array(
				'DELETED_TYPE' => FolderTable::DELETED_TYPE_NONE,
				'PATH_CHILD.PARENT_ID' => $this->id,
			),
			'order' => array('DEPTH_LEVEL' => 'ASC')
		);

		$objectIterator = FolderTable::getList(static::prepareGetListParameters($parameters));
		foreach ($objectIterator as $objectRow)
		{
			if ($objectRow['ID'] == $this->id)
			{
				continue;
			}

			$item = BaseObject::buildFromArray($objectRow);
			if(!isset($mapParentToNewParent[$item->getParentId()]))
			{
				return null;
			}

			/** @var Folder $newParentFolder */
			$newParentFolder = $mapParentToNewParent[$item->getParentId()];
			if($item instanceof File)
			{
				/** @var \Bitrix\Disk\File $item */
				$item->copyTo($newParentFolder, $updatedBy, $generateUniqueName);
			}
			elseif ($item instanceof Folder)
			{
				/** @var \Bitrix\Disk\Folder $item */
				$newFolder = $item->copyToInternal($newParentFolder, $updatedBy, $generateUniqueName);
				if(!$newFolder)
				{
					continue;
				}
				$mapParentToNewParent[$item->getId()] = $newFolder;
			}
		}

		return $newRoot;
	}

	protected function copyToInternal(Folder $targetFolder, $updatedBy, $generateUniqueName = false)
	{
		$newFolder = $targetFolder->addSubFolder(array(
			'NAME' => $this->getName(),
			'CREATED_BY' => $updatedBy,
		), array(), $generateUniqueName);

		if(!$newFolder)
		{
			$this->errorCollection->add($targetFolder->getErrors());
			return null;
		}
		return $newFolder;
	}

	/**
	 * Gets all descendants objects by the folder.
	 * @param SecurityContext $securityContext Security context.
	 * @param array           $parameters Parameters.
	 * @param int             $orderDepthLevel Order for depth level (default asc).
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @return BaseObject[]
	 */
	public function getDescendants(SecurityContext $securityContext, array $parameters = array(), $orderDepthLevel = SORT_ASC)
	{
		if(!isset($parameters['filter']))
		{
			$parameters['filter'] = array();
		}
		if(!isset($parameters['select']))
		{
			$parameters['select'] = array('*');
		}

		if(!empty($parameters['filter']['MIXED_SHOW_DELETED']))
		{
			unset($parameters['filter']['DELETED_TYPE'], $parameters['filter']['MIXED_SHOW_DELETED']);
		}
		elseif (
			!array_key_exists('DELETED_TYPE', $parameters['filter']) &&
			!array_key_exists('!DELETED_TYPE', $parameters['filter']) &&
			!array_key_exists('!=DELETED_TYPE', $parameters['filter']) &&
			!array_key_exists('!==DELETED_TYPE', $parameters['filter'])
		)
		{
			$parameters['filter']['DELETED_TYPE'] = ObjectTable::DELETED_TYPE_NONE;
		}
		$parameters['select']['DEPTH_LEVEL'] = 'PATH_CHILD.DEPTH_LEVEL';
		$parameters = Driver::getInstance()->getRightsManager()->addRightsCheck($securityContext, $parameters, array('ID', 'CREATED_BY'));

		$data = FolderTable::getDescendants($this->id, static::prepareGetListParameters($parameters))->fetchAll();
		if($orderDepthLevel !== null)
		{
			Collection::sortByColumn($data, array('DEPTH_LEVEL' => $orderDepthLevel));
		}

		$modelData = array();
		foreach($data as $item)
		{
			$modelData[] = BaseObject::buildFromArray($item);
		}
		unset($item);

		return $modelData;
	}

	/**
	 * Gets direct children (files, folders).
	 * @param SecurityContext $securityContext Security context.
	 * @param array           $parameters Parameters.
	 * @return BaseObject[]
	 */
	public function getChildren(SecurityContext $securityContext, array $parameters = array())
	{
		if(!isset($parameters['filter']))
		{
			$parameters['filter'] = array();
		}
		if(!empty($parameters['filter']['MIXED_SHOW_DELETED']))
		{
			unset($parameters['filter']['DELETED_TYPE'], $parameters['filter']['MIXED_SHOW_DELETED']);
		}
		elseif (
			!array_key_exists('DELETED_TYPE', $parameters['filter']) &&
			!array_key_exists('!DELETED_TYPE', $parameters['filter']) &&
			!array_key_exists('!=DELETED_TYPE', $parameters['filter']) &&
			!array_key_exists('!==DELETED_TYPE', $parameters['filter'])
		)
		{
			$parameters['filter']['DELETED_TYPE'] = ObjectTable::DELETED_TYPE_NONE;
		}
		$parameters = Driver::getInstance()->getRightsManager()->addRightsCheck($securityContext, $parameters, array('ID', 'CREATED_BY'));

		$modelData = array();
		$query = FolderTable::getChildren($this->id, static::prepareGetListParameters($parameters));
		while($item = $query->fetch())
		{
			$modelData[] = BaseObject::buildFromArray($item);
		}

		return $modelData;
	}

	/**
	 * Returns first child by filter.
	 * @param array $filter Filter.
	 * @param array $with List of eager loading.
	 *
	 * @return Folder|File|BaseObject
	 */
	public function getChild(array $filter, array $with = array())
	{
		$filter['PARENT_ID'] = $this->id;

		return BaseObject::load($filter, $with);
	}

	/**
	 * Checks if folder has children.
	 * @return bool
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function hasChildren(): bool
	{
		return ObjectPathTable::hasChildren($this->getRealObjectId());
	}

	/**
	 * Counts size of all version under folder. Possible to use security context and filter.
	 * @param SecurityContext|null $securityContext Security context.
	 * @param array                $filter Filter.
	 * @return null|int
	 */
	public function countSizeOfVersions(SecurityContext $securityContext = null, array $filter = array())
	{
		$filter = array(
			'WITH_SYMLINKS' => false,
			'WITH_DELETED' => true,
		);

		$query = new Query(VersionTable::getEntity());
		$query
			->registerRuntimeField('', new ExpressionField('FILE_SIZE', 'SUM(SIZE)'))
			->addSelect('FILE_SIZE')
			->addFilter('=PATH_CHILD.PARENT_ID', $this->id)
		;

		$result = $query->exec();
		$row = $result->fetch();
		if(isset($row['FILE_SIZE']))
		{
			return $row['FILE_SIZE'];
		}

		return null;
	}

	/**
	 * Counts size of head version under folder. Possible to use security context and filter.
	 * @param SecurityContext|null $securityContext Security context.
	 * @param array                $filter Filter.
	 * @return null|int
	 */
	public function countSizeOfFiles(SecurityContext $securityContext = null, array $filter = array())
	{
		return $this->countSizeHeadOfVersions($securityContext, $filter);
	}

	/**
	 * Counts size of head version under folder. Possible to use security context and filter.
	 * @param SecurityContext|null $securityContext Security context.
	 * @param array                $filter Filter.
	 * @return null|int
	 */
	public function countSizeHeadOfVersions(SecurityContext $securityContext = null, array $filter = array())
	{
		$filter = array(
			'WITH_SYMLINKS' => false,
			'WITH_DELETED' => true,
		);

		$query = new Query(FolderTable::getEntity());
		$query
			->registerRuntimeField('', new ExpressionField('FILE_SIZE', 'SUM(SIZE)'))
			->addSelect('FILE_SIZE')
			->addFilter('=PATH_CHILD.PARENT_ID', $this->id)
		;

		$result = $query->exec();
		$row = $result->fetch();
		if(isset($row['FILE_SIZE']))
		{
			return $row['FILE_SIZE'];
		}

		return null;
	}

	/**
	 * Marks deleted object. It equals to move in trash can.
	 * @param int $deletedBy Id of user (or SystemUser::SYSTEM_USER_ID).
	 * @return bool
	 */
	public function markDeleted($deletedBy)
	{
		if ($this->deletedType == ObjectTable::DELETED_TYPE_ROOT)
		{
			return true;
		}

		$this->errorCollection->clear();

		$driver = Driver::getInstance();
		$driver->getSubscriberManager()->preloadSharingsForSubtree($this);

		$parameters = array(
			'select' => array(
				'*',
				'DEPTH_LEVEL' => 'PATH_CHILD.DEPTH_LEVEL',
			),
			'filter' => array(
				'PATH_CHILD.PARENT_ID' => $this->id,
			),
			'order' => array('DEPTH_LEVEL' => 'DESC')
		);

		$success = true;
		$objectIterator = FolderTable::getList(static::prepareGetListParameters($parameters));
		foreach ($objectIterator as $objectRow)
		{
			if ($objectRow['ID'] == $this->id)
			{
				//to modify current object. Don't make another instance of $this->id
				$object = $this;
			}
			else
			{
				$object = BaseObject::buildFromArray($objectRow);
			}

			/** @var Folder|File */
			if($object instanceof Folder)
			{
				$deleteType = ObjectTable::DELETED_TYPE_CHILD;
				if ($objectRow['ID'] == $this->id)
				{
					$deleteType = ObjectTable::DELETED_TYPE_ROOT;
				}

				/** @see \Bitrix\Disk\Folder::markDeletedNonRecursiveInternal */
				$success = $object->markDeletedNonRecursiveInternal($deletedBy, $deleteType);
			}
			elseif($object instanceof File)
			{
				$success = $object->markDeletedInternal($deletedBy, ObjectTable::DELETED_TYPE_CHILD);
			}
		}

		$driver->getDeletedLogManager()->finalize();
		$driver->getDeletionNotifyManager()->send();

		return $success;
	}

	protected function markDeletedNonRecursiveInternal($deletedBy, $deletedType = ObjectTable::DELETED_TYPE_ROOT)
	{
		$alreadyDeleted = $this->isDeleted();
		$success = parent::markDeletedInternal($deletedBy, $deletedType);
		if ($success && !$alreadyDeleted)
		{
			Driver::getInstance()->getDeletedLogManager()->mark($this, $deletedBy);
		}

		return $success;
	}

	/**
	 * Restores object from trash can.
	 * @param int $restoredBy Id of user (or SystemUser::SYSTEM_USER_ID).
	 * @return bool
	 */
	public function restore($restoredBy)
	{
		if (!$this->isDeleted())
		{
			return true;
		}

		$this->errorCollection->clear();

		$parameters = array(
			'select' => array(
				'*',
				'DEPTH_LEVEL' => 'PATH_CHILD.DEPTH_LEVEL',
			),
			'filter' => array(
				'PATH_CHILD.PARENT_ID' => $this->id,
				'!==DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
			),
			'order' => array('DEPTH_LEVEL' => 'DESC')
		);

		$objectIterator = FolderTable::getList(static::prepareGetListParameters($parameters));
		foreach ($objectIterator as $objectRow)
		{
			if ($objectRow['ID'] == $this->id)
			{
				continue;
			}

			$object = BaseObject::buildFromArray($objectRow);

			/** @var Folder|File */
			if($object instanceof Folder)
			{
				/** @see \Bitrix\Disk\Folder::restoreNonRecursive */
				$object->restoreNonRecursive($restoredBy);
			}
			elseif($object instanceof File)
			{
				$object->restoreInternal($restoredBy);
			}
		}

		$needRecalculate = $this->deletedType == ObjectTable::DELETED_TYPE_CHILD;
		$statusRestoreNonRecursive = $this->restoreInternal($restoredBy);
		if($statusRestoreNonRecursive && $needRecalculate)
		{
			$this->recalculateDeletedTypeAfterRestore($restoredBy);
		}

		if($statusRestoreNonRecursive)
		{
			Driver::getInstance()->sendChangeStatusToSubscribers($this);
		}

		return $statusRestoreNonRecursive;
	}

	protected function restoreNonRecursive($restoredBy)
	{
		return parent::restoreInternal($restoredBy);
	}

	/**
	 * Deletes folder and all descendants objects.
	 * @param int $deletedBy Id of user (or SystemUser::SYSTEM_USER_ID).
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @return bool
	 */
	public function deleteTree($deletedBy)
	{
		$this->errorCollection->clear();

		$parameters = array(
			'select' => array(
				'*',
				'DEPTH_LEVEL' => 'PATH_CHILD.DEPTH_LEVEL',
			),
			'filter' => array(
				'PATH_CHILD.PARENT_ID' => $this->id,
			),
			'order' => array('DEPTH_LEVEL' => 'DESC')
		);

		$success = true;
		$objectIterator = FolderTable::getList(static::prepareGetListParameters($parameters));
		foreach ($objectIterator as $objectRow)
		{
			if ($objectRow['ID'] == $this->id)
			{
				//to modify current object. Don't make another instance of $this->id
				$object = $this;
			}
			else
			{
				$object = BaseObject::buildFromArray($objectRow);
			}

			/** @var Folder|File */
			if($object instanceof Folder)
			{
				/** @see \Bitrix\Disk\Folder::deleteNonRecursive */
				$success = $object->deleteNonRecursive($deletedBy);
			}
			elseif($object instanceof File)
			{
				/** @see \Bitrix\Disk\File::delete */
				$success = $object->delete($deletedBy);
			}
		}

		return $success;
	}

	protected function deleteNonRecursive($deletedBy)
	{
		foreach($this->getSharingsAsReal() as $sharing)
		{
			$sharing->delete($deletedBy);
		}

		//with status unreplied, declined (not approved)
		$success = SharingTable::deleteByFilter(array(
			'REAL_OBJECT_ID' => $this->id,
		));

		if(!$success)
		{
			return false;
		}

		SimpleRightTable::deleteBatch(array('OBJECT_ID' => $this->id));

		$success = RightTable::deleteByFilter(array(
			'OBJECT_ID' => $this->id,
		));

		if(!$success)
		{
			return false;
		}
		Driver::getInstance()->getIndexManager()->dropIndex($this);
		Driver::getInstance()->getDeletedLogManager()->mark($this, $deletedBy);

		$resultDelete = FolderTable::delete($this->id);
		if(!$resultDelete->isSuccess())
		{
			return false;
		}

		if(!$this->isLink())
		{
			//todo potential - very hard operation.
			foreach(Folder::getModelList(array('filter' => array('REAL_OBJECT_ID' => $this->id))) as $link)
			{
				$link->deleteTree($deletedBy);
			}
		}

		$event = new Event(Driver::INTERNAL_MODULE_ID, "onAfterDeleteFolder", array($this->getId(), $deletedBy));
		$event->send();

		return true;
	}

	/**
	 * Returns size of all files with only head version.
	 * Without symbolic links.
	 *
	 * @param null $filter
	 *
	 * @return int|null
	 */
	public function getSize($filter = null)
	{
		$query = new Query(FileTable::getEntity());
		$query
			->registerRuntimeField('', new ExpressionField('FILE_SIZE', 'SUM(SIZE)'))
			->addSelect('FILE_SIZE')
			->addFilter('=PATH_CHILD.PARENT_ID', $this->getRealObjectId())
			->addFilter('=STORAGE_ID', $this->getStorageId())
			->addFilter('=TYPE', FileTable::TYPE)
			->addFilter('=DELETED_TYPE', FileTable::DELETED_TYPE_NONE)
		;

		$result = $query->exec();
		$row = $result->fetch();
		if (isset($row['FILE_SIZE']))
		{
			return $row['FILE_SIZE'];
		}

		return null;
	}


	/**
	 * Returns the list of pair for mapping.
	 * Key is field in DataManager, value is object property.
	 * @return array
	 */
	public static function getMapAttributes()
	{
		static $shelve = null;
		if($shelve !== null)
		{
			return $shelve;
		}

		$shelve = array_merge(parent::getMapAttributes(), array(
			'HAS_SUBFOLDERS' => 'hasSubFolders',
		));

		return $shelve;
	}
}

/**
 * Class SpecificFolder
 * @package Bitrix\Disk
 * @internal
 */
final class SpecificFolder
{
	const CODE_FOR_CREATED_FILES  = 'FOR_CREATED_FILES';
	const CODE_FOR_SAVED_FILES    = 'FOR_SAVED_FILES';
	const CODE_FOR_UPLOADED_FILES = 'FOR_UPLOADED_FILES';
	const CODE_FOR_RECORDED_FILES = 'FOR_RECORDED_FILES';

	const CODE_FOR_IMPORT_DROPBOX  = 'FOR_DROPBOX_FILES';
	const CODE_FOR_IMPORT_ONEDRIVE = 'FOR_ONEDRIVE_FILES';
	const CODE_FOR_IMPORT_GDRIVE   = 'FOR_GDRIVE_FILES';
	const CODE_FOR_IMPORT_BOX      = 'FOR_BOX_FILES';
	const CODE_FOR_IMPORT_YANDEX   = 'FOR_YANDEXDISK_FILES';

	/**
	 * Gets name for specific folder by code. If code is invalid, then return null.
	 * @param string $code Code of specific folder.
	 * @return null|string
	 */
	public static function getName($code)
	{
		$codes = static::getCodes();
		if(!isset($codes[$code]))
		{
			return null;
		}
		return Loc::getMessage("DISK_FOLDER_SPECIFIC_{$code}_NAME");
	}

	/**
	 * Gets specific folder in storage by code. If folder does not exist, creates it.
	 * @param Storage $storage Target storage.
	 * @param string $code Code of specific folder.
	 * @return Folder|null|static
	 */
	public static function getFolder(Storage $storage, $code)
	{
		$folder = Folder::load(array(
			'=CODE' => $code,
			'STORAGE_ID' => $storage->getId(),
		));
		if($folder)
		{
			return $folder;
		}

		return static::createFolder($storage, $code);
	}

	protected static function createFolder(Storage $storage, $code)
	{
		$name = static::getName($code);
		if(!$name)
		{
			return null;
		}

		if($storage->getProxyType() instanceof ProxyType\User)
		{
			$createdBy = $storage->getEntityId();
		}
		else
		{
			$createdBy = SystemUser::SYSTEM_USER_ID;
		}

		if(static::shouldBeUnderUploadedFolder($code))
		{
			$folderForUploadedFiles = $storage->getFolderForUploadedFiles();
			if(!$folderForUploadedFiles)
			{
				return null;
			}
			return $folderForUploadedFiles->addSubFolder(array(
				'NAME' => $name,
				'CODE' => $code,
				'CREATED_BY' => $createdBy
			), array(), true);
		}

		return $storage->addFolder(array(
			'NAME' => $name,
			'CODE' => $code,
			'CREATED_BY' => $createdBy
		), array(), true);
	}

	protected static function shouldBeUnderUploadedFolder($code)
	{
		return
			static::CODE_FOR_IMPORT_DROPBOX === $code ||
			static::CODE_FOR_IMPORT_ONEDRIVE === $code ||
			static::CODE_FOR_IMPORT_YANDEX === $code ||
			static::CODE_FOR_IMPORT_BOX === $code ||
			static::CODE_FOR_IMPORT_GDRIVE === $code;
	}

	protected static function getCodes()
	{
		static $codes = null;
		if($codes !== null)
		{
			return $codes;
		}
		$refClass = new \ReflectionClass(__CLASS__);
		foreach($refClass->getConstants() as $name => $value)
		{
			if(mb_substr($name, 0, 4) === 'CODE')
			{
				$codes[$value] = $value;
			}
		}
		unset($name, $value);

        return $codes;
	}
}
