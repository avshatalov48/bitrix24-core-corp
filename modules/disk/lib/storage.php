<?php

namespace Bitrix\Disk;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\StorageTable;
use Bitrix\Disk\Internals\VersionTable;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Result;
use Bitrix\Main\Event;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

final class Storage extends Internals\Model
{
	const ERROR_NOT_EXISTS_ROOT_OBJECT = 'DISK_ST_22001';
	const ERROR_RENAME_ROOT_OBJECT     = 'DISK_ST_22002';
	const ERROR_ROOT_OBJECT_NOT_FOLDER = 'DISK_ST_22003';

	/** @var string */
	protected $name;
	/** @var string */
	protected $code;
	/** @var string */
	protected $xmlId;
	/** @var string */
	protected $moduleId;
	/** @var string */
	protected $entityType;
	/** @var string */
	protected $entityId;
	/** @var string */
	protected $entityMiscData;
	/** @var int */
	protected $rootObjectId;
	/** @var  Folder */
	protected $rootObject;
	/** @var bool */
	protected $useInternalRights;
	/** @var string */
	protected $siteId;
	/** @var SecurityContext[] */
	protected $cacheSecurityContext = array();
	/** @var ProxyType\Base */
	protected $proxyType;

	/**
	 * @var Storage[]
	 */
	protected static $loadedStorages = array();

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getTableClassName()
	{
		return StorageTable::className();
	}

	/**
	 * @param SecurityContext $securityContext
	 * @return bool
	 */
	public function canAdd(SecurityContext $securityContext)
	{
		$folder = $this->getRootObject();

		return $folder ? $folder->canAdd($securityContext) : false;
	}

	/**
	 * @param SecurityContext $securityContext
	 * @return bool
	 */
	public function canRead(SecurityContext $securityContext)
	{
		$folder = $this->getRootObject();

		return $folder ? $folder->canRead($securityContext) : false;
	}

	/**
	 * @param SecurityContext $securityContext
	 * @return bool
	 */
	public function canChangeSettings(SecurityContext $securityContext)
	{
		return $securityContext->canChangeSettings($this->rootObjectId);
	}

	/**
	 * @param SecurityContext $securityContext
	 * @return bool
	 */
	public function canChangeRights(SecurityContext $securityContext)
	{
		return $securityContext->canChangeRights($this->rootObjectId);
	}

	/**
	 * @param SecurityContext $securityContext
	 * @return bool
	 */
	public function canCreateWorkflow(SecurityContext $securityContext)
	{
		return $securityContext->canCreateWorkflow($this->rootObjectId);
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * @return string
	 */
	public function getXmlId()
	{
		return $this->xmlId;
	}

	/**
	 * @return string
	 */
	public function getEntityId()
	{
		return $this->entityId;
	}

	/**
	 * @return string
	 */
	public function getEntityType()
	{
		return $this->entityType;
	}

	/**
	 * @return string
	 */
	public function getEntityMiscData()
	{
		return $this->entityMiscData;
	}

	/**
	 * @return array
	 */
	protected function getUnserializedMiscData()
	{
		if(!empty($this->entityMiscData) && is_string($this->entityMiscData))
		{
			return unserialize($this->entityMiscData, ['allowed_classes' => false]);
		}

		return array();
	}

	/**
	 * @param $entityMiscData
	 * @return null|string
	 */
	protected function getSerializedMiscData($entityMiscData)
	{
		if(!empty($entityMiscData))
		{
			return serialize($entityMiscData);
		}

		return null;
	}

	protected function getValueMiscData($name)
	{
		$miscData = $this->getUnserializedMiscData();
		if(is_array($miscData) && isset($miscData[$name]))
		{
			return $miscData[$name];
		}

		return null;
	}

	protected function setValueInMiscData($name, $value)
	{
		$entityMiscDataArray = $this->getUnserializedMiscData();
		$entityMiscDataArray[$name] = $value;
		$entityMiscData = $this->getSerializedMiscData($entityMiscDataArray);
		if(empty($entityMiscData))
		{
			return false;
		}

		return $this->update(array(
			'ENTITY_MISC_DATA' => $entityMiscData
		));
	}

	/**
	 * Enable BizProc
	 * @return bool
	 */
	public function enableBizProc()
	{
		if($this->getProxyType() instanceof ProxyType\User)
		{
			$this->errorCollection->addOne(new Error('Could not enable bizproc in user storage.'));

			return false;
		}

		return $this->setValueInMiscData('BIZPROC_ENABLED', true);
	}

	/**
	 * Disable BizProc
	 * @return bool
	 */
	public function disableBizProc()
	{
		return $this->setValueInMiscData('BIZPROC_ENABLED', false);
	}

	public function isEnabledBizProc()
	{
		return $this->getValueMiscData('BIZPROC_ENABLED');
	}

	/**
	 * Enable Transformation
	 * @return bool
	 */
	public function enableTransformation()
	{
		return $this->setValueInMiscData('TRANSFORMATION_ENABLED', true);
	}

	/**
	 * Disable Transformation
	 * @return bool
	 */
	public function disableTransformation()
	{
		return $this->setValueInMiscData('TRANSFORMATION_ENABLED', false);
	}

	/**
	 * @return bool
	 */
	public function isEnabledTransformation()
	{
		return $this->getValueMiscData('TRANSFORMATION_ENABLED') || $this->getValueMiscData('TRANSFORMATION_ENABLED') === null;
	}

	public function enableShowExtendedRights()
	{
		return $this->setValueInMiscData('SHOW_EXTENDED_RIGHTS', true);
	}

	public function disableShowExtendedRights()
	{
		return $this->setValueInMiscData('SHOW_EXTENDED_RIGHTS', false);
	}

	/**
	 * Returns size limit for the storage.
	 * Size in bytes.
	 *
	 * @return int|null
	 */
	public function getSizeLimit()
	{
		if(!Configuration::isEnabledStorageSizeRestriction())
		{
			return null;
		}

		return $this->getValueMiscData('SIZE_LIMIT');
	}

	/**
	 * Tells if size limit restriction is enabled for the storage.
	 *
	 * @return bool
	 */
	public function isEnabledSizeLimitRestriction()
	{
		return $this->getSizeLimit() !== null;
	}

	/**
	 * Sets max size storages in bytes. If set to null, then limit will be disabled.
	 *
	 * @param int $bytes Max size in bytes.
	 * @return bool
	 */
	public function setSizeLimit($bytes)
	{
		if(!Configuration::isEnabledStorageSizeRestriction())
		{
			return false;
		}

		if($bytes !== null)
		{
			$bytes = (int)$bytes;
		}

		return $this->setValueInMiscData('SIZE_LIMIT', $bytes);
	}

	/**
	 * Returns size of all files with versions from storage without symbolic links.
	 * Size in bytes.
	 *
	 * @return null|int
	 */
	private function getSize()
	{
		return $this->getRootObject()->countSizeOfVersions();
	}

	/**
	 * Tells if is possible to upload new content, which has size $fileSize, to the storage.
	 *
	 * @param int $fileSize Size in bytes.
	 * @return bool
	 * @internal
	 */
	public function isPossibleToUpload($fileSize)
	{
		if(!$this->isEnabledSizeLimitRestriction())
		{
			return true;
		}

		return $this->getSizeLimit() >= $this->getSize() + $fileSize;
	}

	/**
	 * @return bool|null
	 */
	public function isEnabledShowExtendedRights()
	{
		return $this->getValueMiscData('SHOW_EXTENDED_RIGHTS');
	}

	/**
	 * Changes base url of storage (ProxyType/Common)
	 * @param string $baseUrl Base url of storage.
	 * @return bool
	 */
	public function changeBaseUrl($baseUrl)
	{
		if(!$this->getProxyType() instanceof ProxyType\Common)
		{
			$this->errorCollection->addOne(new Error('Could not set base url to storage. Storage must have ProxyType\Common'));

			return false;
		}

		$success = $this->setValueInMiscData('BASE_URL', $baseUrl);
		if($success)
		{
			$this->rebuildProxyType();
		}

		return $success;
	}

	/**
	 * @return string
	 */
	public function getModuleId()
	{
		return $this->moduleId;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return int
	 */
	public function getRootObjectId()
	{
		return $this->rootObjectId;
	}

	/**
	 * @return Folder|null
	 */
	public function getRootObject()
	{
		if(!$this->rootObjectId)
		{
			return null;
		}

		if(isset($this->rootObject) && $this->rootObjectId === $this->rootObject->getId())
		{
			return $this->rootObject;
		}
		//todo - Storage - knows about Folder ^( Nu i pust'
		$this->rootObject = Folder::loadById($this->rootObjectId);

		return $this->rootObject;
	}

	/**
	 * @return string
	 */
	public function getSiteId()
	{
		return $this->siteId;
	}

	/**
	 * @return bool
	 */
	public function getUseInternalRights()
	{
		return $this->isUseInternalRights();
	}

	/**
	 * @return bool
	 */
	public function isUseInternalRights()
	{
		return $this->useInternalRights;
	}

	/**
	 * @return Security\SecurityContext
	 */
	public static function getFakeSecurityContext()
	{
		return Driver::getInstance()->getFakeSecurityContext();
	}

	/**
	 * @param $user
	 * @return Security\SecurityContext
	 */
	public function getSecurityContext($user)
	{
		//todo Mistake? We decided, SecurityContext should parse USER self. But we would not create typical SecurityContext (cache. cache)
		$userId = null;
		if ($user instanceof CurrentUser)
		{
			return $this->getSecurityContext($user->getId());
		}

		if ($user instanceof \CUser)
		{
			if ($user->isAuthorized())
			{
				$userId = $user->getId();
			}
		}
		elseif ((int)$user > 0)
		{
			$userId = (int)$user;
		}

		if ($userId === null)
		{
			return $this->getProxyType()->getSecurityContextByUser($user);
		}
		if (!isset($this->cacheSecurityContext[$userId]))
		{
			$this->cacheSecurityContext[$userId] = $this->getProxyType()->getSecurityContextByUser($user);
		}

		return $this->cacheSecurityContext[$userId];
	}

	/**
	 * @return Security\SecurityContext
	 */
	public function getCurrentUserSecurityContext()
	{
		global $USER;

		return $this->getSecurityContext($USER);
	}

	/**
	 * Creates or loads folder for saving created files (ex. in cloud services)
	 * @return Folder|null
	 */
	public function getFolderForCreatedFiles()
	{
		return $this->getSpecificFolderByCode(SpecificFolder::CODE_FOR_CREATED_FILES);
	}

	/**
	 * Creates or loads folder for saving "saved" files
	 * @return Folder|null
	 */
	public function getFolderForSavedFiles()
	{
		return $this->getSpecificFolderByCode(SpecificFolder::CODE_FOR_SAVED_FILES);
	}

	/**
	 * Creates or loads folder for saving "uploaded" files
	 * @return Folder|null
	 */
	public function getFolderForUploadedFiles()
	{
		return $this->getSpecificFolderByCode(SpecificFolder::CODE_FOR_UPLOADED_FILES);
	}

	/**
	 * Creates or loads folder for saving "recorded" files
	 * @return Folder|null
	 */
	public function getFolderForRecordedFiles()
	{
		return $this->getSpecificFolderByCode(SpecificFolder::CODE_FOR_RECORDED_FILES);
	}

	/**
	 * Creates or loads specific folder by symbolic code.
	 * @param string $code Code of specific folder.
	 * @return Folder|null Specific folder.
	 */
	public function getSpecificFolderByCode($code)
	{
		return SpecificFolder::getFolder($this, $code);
	}

	/**
	 * @return ProxyType\Base
	 */
	public function getProxyType()
	{
		if($this->proxyType === null)
		{
			$this->proxyType = $this->initializeProxyType();
		}

		return $this->proxyType;
	}

	/**
	 * @return ProxyType\Base
	 */
	public function rebuildProxyType()
	{
		if($this->proxyType !== null)
		{
			unset($this->proxyType);
		}

		return $this->getProxyType();
	}

	/**
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\Main\LoaderException
	 * @return ProxyType\Base
	 */
	protected function initializeProxyType()
	{
		if(Driver::INTERNAL_MODULE_ID != $this->moduleId && !Loader::includeModule($this->moduleId))
		{
			throw new SystemException("Could not include module {$this->moduleId}");
		}

		/** @var ProxyType\Base $entityTypeClassName */
		$entityTypeClassName = $this->entityType;
		/** @var ProxyType\Base $proxyType */
		$proxyType = new $entityTypeClassName($this->entityId, $this, $this->entityMiscData);

		if(!$proxyType instanceof ProxyType\Base)
		{
			throw new SystemException('Invalid class for ProxyType. Must be instance of ProxyType\Base');
		}

		return $proxyType;
	}

	/**
	 * @param SecurityContext $securityContext
	 * @param array           $parameters
	 * @return array|BaseObject[]
	 */
	public function getChildren(SecurityContext $securityContext, array $parameters = array())
	{
		$rootFolder = $this->getRootObject();
		if(!$rootFolder)
		{
			return array();
		}

		return $rootFolder->getChildren($securityContext, $parameters);
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
		$rootFolder = $this->getRootObject();
		if(!$rootFolder)
		{
			return null;
		}

		return $rootFolder->getChild($filter, $with);
	}

	/**
	 * @param SecurityContext $securityContext
	 * @param array           $parameters
	 * @param int             $orderDepthLevel
	 * @return array|\Object[]
	 */
	public function getDescendants(SecurityContext $securityContext, array $parameters = array(), $orderDepthLevel = SORT_ASC)
	{
		$rootFolder = $this->getRootObject();
		if(!$rootFolder)
		{
			return array();
		}

		return $rootFolder->getDescendants($securityContext, $parameters, $orderDepthLevel);
	}

	/**
	 * Add new file to folder.
	 * @param array $fileArray structure like $_FILES
	 * @param array $data contains additional fields (CREATED_BY, NAME, etc).
	 * @param array $rights
	 * @param bool  $generateUniqueName
	 * @return File|null
	 */
	public function uploadFile(array $fileArray, array $data, array $rights = array(), $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		$rootFolder = $this->getRootObject();
		if(!$rootFolder)
		{
			$this->errorCollection->add(array(new Error("Storage doesn't have root folder.", self::ERROR_NOT_EXISTS_ROOT_OBJECT)));

			return null;
		}
		$fileModel = $rootFolder->uploadFile($fileArray, $data, $rights, $generateUniqueName);
		if(!$fileModel)
		{
			$this->errorCollection->add($rootFolder->getErrors());

			return null;
		}

		return $fileModel;
	}

	/**
	 * Create in folder blank file (size 0 byte).
	 * @param array $data
	 * @param array $rights
	 * @param bool  $generateUniqueName
	 * @return File|null
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function addBlankFile(array $data, array $rights = array(), $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		$rootFolder = $this->getRootObject();
		if(!$rootFolder)
		{
			$this->errorCollection->add(array(new Error("Storage doesn't have root folder.", self::ERROR_NOT_EXISTS_ROOT_OBJECT)));

			return null;
		}
		$fileModel = $rootFolder->addBlankFile($data, $rights, $generateUniqueName);
		if(!$fileModel)
		{
			$this->errorCollection->add($rootFolder->getErrors());

			return null;
		}

		return $fileModel;
	}

	/**
	 * @param array $data
	 * @param array $rights
	 * @param bool  $generateUniqueName
	 * @return File|null
	 */
	public function addFile(array $data, array $rights = array(), $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		$rootFolder = $this->getRootObject();
		if(!$rootFolder)
		{
			$this->errorCollection->add(array(new Error("Storage doesn't have root folder.", self::ERROR_NOT_EXISTS_ROOT_OBJECT)));

			return null;
		}
		$fileModel = $rootFolder->addFile($data, $rights, $generateUniqueName);
		if(!$fileModel)
		{
			$this->errorCollection->add($rootFolder->getErrors());

			return null;
		}

		return $fileModel;
	}

	/**
	 * @param File $sourceFile
	 * @param array $data
	 * @param array $rights
	 * @param bool $generateUniqueName
	 * @return FileLink|null
	 */
	public function addFileLink(File $sourceFile, array $data, array $rights = array(), $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		$rootFolder = $this->getRootObject();
		if(!$rootFolder)
		{
			$this->errorCollection->add(array(new Error("Storage doesn't have root folder.", self::ERROR_NOT_EXISTS_ROOT_OBJECT)));

			return null;
		}
		$fileLinkModel = $rootFolder->addFileLink($sourceFile, $data, $rights, $generateUniqueName);
		if(!$fileLinkModel)
		{
			$this->errorCollection->add($rootFolder->getErrors());

			return null;
		}

		return $fileLinkModel;
	}

	/**
	 * @param array $data
	 * @param array $rights
	 * @param bool  $generateUniqueName
	 * @return Folder|null
	 */
	public function addFolder(array $data, array $rights = array(), $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		$rootFolder = $this->getRootObject();
		if(!$rootFolder)
		{
			$this->errorCollection->add(array(new Error("Storage doesn't have root folder.", self::ERROR_NOT_EXISTS_ROOT_OBJECT)));

			return null;
		}
		$folderModel = $rootFolder->addSubFolder($data, $rights, $generateUniqueName);
		if(!$folderModel)
		{
			$this->errorCollection->add($rootFolder->getErrors());

			return null;
		}

		return $folderModel;
	}

	/**
	 * @param Folder $sourceFolder
	 * @param array  $data
	 * @param array  $rights
	 * @param bool   $generateUniqueName
	 * @return FolderLink|null
	 */
	public function addFolderLink(Folder $sourceFolder, array $data, array $rights = array(), $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		$rootFolder = $this->getRootObject();
		if(!$rootFolder)
		{
			$this->errorCollection->add(array(new Error("Storage doesn't have root folder.", self::ERROR_NOT_EXISTS_ROOT_OBJECT)));

			return null;
		}
		if(!$rootFolder instanceof Folder)
		{
			$this->errorCollection->add(array(new Error("Storage doesn't have root folder.", self::ERROR_ROOT_OBJECT_NOT_FOLDER)));

			return null;
		}
		$folderLinkModel = $rootFolder->addSubFolderLink($sourceFolder, $data, $rights, $generateUniqueName);
		if(!$folderLinkModel)
		{
			$this->errorCollection->add($rootFolder->getErrors());

			return null;
		}

		return $folderLinkModel;
	}

	/*
	 * @inheritdoc
	 */
	public static function add(array $data, ErrorCollection $errorCollection)
	{
		if(!is_subclass_of($data['ENTITY_TYPE'], ProxyType\Base::className()))
		{
			throw new SystemException('Invalid class for ProxyType. Must be subclass of ProxyType\Base');
		}

		$rootObjectData = array();
		if(!empty($data['ROOT_OBJECT']))
		{
			$rootObjectData = $data['ROOT_OBJECT'];
			unset($data['ROOT_OBJECT']);
		}

		$storage = parent::add($data, $errorCollection);
		if(!$storage)
		{
			return null;
		}

		$folderData = array_merge(array_intersect_key($rootObjectData, array(
			'CREATE_TIME' => true,
			'UPDATE_TIME' => true,
			'XML_ID' => true,
		)), array(
			'NAME' => Ui\Text::correctFilename($storage->getName()),
			'STORAGE_ID' => $storage->getId(),
		));

		$folder = Folder::add($folderData, $errorCollection);

		if(!$folder)
		{
			return null;
		}

		$success = $storage->update(array('ROOT_OBJECT_ID' => $folder->getId()));
		if(!$success)
		{
			return null;
		}

		$storage->rootObject = $folder;

		$event = new Event(Driver::INTERNAL_MODULE_ID, "onAfterAddStorage", array($storage));
		$event->send();

		$storage->clearByTagCommonStorages();

		return $storage;
	}

	/**
	 * Changes name of storage.
	 * @param string $name New name for storage.
	 * @return bool
	 */
	public function rename($name)
	{
		$this->errorCollection->clear();

		$rootFolder = $this->getRootObject();
		if(!$rootFolder)
		{
			$this->errorCollection->add(array(new Error("Storage doesn't have root folder.", self::ERROR_NOT_EXISTS_ROOT_OBJECT)));

			return false;
		}
		if(!$rootFolder->rename(Ui\Text::correctFilename($name)))
		{
			$this->errorCollection->add(array(new Error("Could not rename name of root folder.", self::ERROR_RENAME_ROOT_OBJECT)));

			return false;
		}

		return $this->update(array('NAME' => $name));
	}

	public function delete($deletedBy)
	{
		if($this->getRootObject() && !$this->getRootObject()->deleteTree($deletedBy))
		{
			return false;
		}
		$status = parent::deleteInternal();
		if($status)
		{
			$event = new Event(Driver::INTERNAL_MODULE_ID, "onAfterDeleteStorage", array($this->getId(), $deletedBy));
			$event->send();

			$this->clearByTagCommonStorages();
		}

		return $status;
	}

	private function clearByTagCommonStorages()
	{
		if(defined('BX_COMP_MANAGED_CACHE') && $this->getProxyType() instanceof ProxyType\Common)
		{
			Application::getInstance()->getTaggedCache()->clearByTag('disk_common_storage');
		}
	}

	/**
	 * @inheritdoc
	 */
	public static function loadById($id, array $with = array())
	{
		if(self::isLoaded($id))
		{
			return self::$loadedStorages[$id];
		}
		self::$loadedStorages[$id] = parent::loadById($id, $with);

		return self::$loadedStorages[$id];
	}

	public static function isLoaded($id)
	{
		return isset(self::$loadedStorages[$id]);
	}

	/**
	 * Builds model from array.
	 * @param array $attributes Model attributes.
	 * @param array &$aliases Aliases.
	 * @internal
	 * @return static
	 */
	public static function buildFromArray(array $attributes, array &$aliases = null)
	{
		if(self::isLoaded($attributes['ID']))
		{
			return self::$loadedStorages[$attributes['ID']];
		}
		self::$loadedStorages[$attributes['ID']] = parent::buildFromArray($attributes, $aliases);

		return self::$loadedStorages[$attributes['ID']];
	}

	/**
	 * Builds model from \Bitrix\Main\Entity\Result.
	 * @param Result $result Query result.
	 * @return static
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function buildFromResult(Result $result)
	{
		/** @var Storage $model */
		$model = parent::buildFromResult($result);
		self::$loadedStorages[$model->getId()] = $model;

		return $model;
	}

	/**
	 * Returns readable storages by SecurityContext.
	 * Be careful! The method works under FolderTable and $parameters belongs to FolderTable.
	 *
	 * @param SecurityContext $securityContext SecurityContext.
	 * @param array           $parameters Parameters to getList.
	 * @return Storage[]
	 */
	public static function getReadableList(SecurityContext $securityContext, array $parameters = array())
	{
		if (empty($parameters['with']))
		{
			$parameters['with'] = array();
		}

		$conditionTree = Query::filter();
		$conditionTree
			->whereColumn('ID', 'STORAGE.ROOT_OBJECT_ID')
			->where('STORAGE.MODULE_ID', Driver::INTERNAL_MODULE_ID)
			->where('RIGHTS_CHECK', true)
		;

		$filter = [
			'=PARENT_ID' => null,
			'=STORAGE.MODULE_ID' => Driver::INTERNAL_MODULE_ID,
			'=RIGHTS_CHECK' => true,
		];

		if (empty($parameters['filter']))
		{
			$parameters['filter'] = Query::filter();
		}

		if ($parameters['filter'] instanceof ConditionTree)
		{
			$parameters['filter'] = $conditionTree->addCondition($parameters['filter']);
		}
		elseif (is_array($parameters['filter']))
		{
			$parameters['filter'] = array_merge($parameters['filter'], $filter);
		}

		$parameters['with'] = array_merge($parameters['with'], array('STORAGE'));

		$parameters = Driver::getInstance()->getRightsManager()->addRightsCheck($securityContext, $parameters, array(
			'ID',
			'CREATED_BY'
		));

		/** @var Folder[] $items */
		$items = Folder::getModelList($parameters);
		$storages = array();
		foreach ($items as $item)
		{
			$item->getStorage()->setAttributes(array('ROOT_OBJECT' => $item));
			$storages[] = $item->getStorage();
		}

		return $storages;
	}

	/*
	 * @inheritdoc
	 */
	public static function getMapAttributes()
	{
		return array(
			'ID' => 'id',
			'NAME' => 'name',
			'CODE' => 'code',
			'XML_ID' => 'xmlId',
			'MODULE_ID' => 'moduleId',
			'ENTITY_TYPE' => 'entityType',
			'ENTITY_ID' => 'entityId',
			'ENTITY_MISC_DATA' => 'entityMiscData',
			'ROOT_OBJECT_ID' => 'rootObjectId',
			'ROOT_OBJECT' => 'rootObject',
			'USE_INTERNAL_RIGHTS' => 'useInternalRights',
			'SITE_ID' => 'siteId',
		);
	}

	/*
	 * @inheritdoc
	 */
	public static function getMapReferenceAttributes()
	{
		return array(
			'ROOT_OBJECT' => Folder::className(),
		);
	}

	protected static function prepareGetListParameters(array $parameters)
	{
		if(isset($parameters['filter']['ENTITY_TYPE']))
		{
			$parameters['filter']['=ENTITY_TYPE'] = $parameters['filter']['ENTITY_TYPE'];
			unset($parameters['filter']['ENTITY_TYPE']);
		}
		if(isset($parameters['filter']['ENTITY_ID']))
		{
			$parameters['filter']['=ENTITY_ID'] = $parameters['filter']['ENTITY_ID'];
			unset($parameters['filter']['ENTITY_ID']);
		}
		if(isset($parameters['filter']['CODE']))
		{
			$parameters['filter']['=CODE'] = $parameters['filter']['CODE'];
			unset($parameters['filter']['CODE']);
		}
		if(isset($parameters['filter']['XML_ID']))
		{
			$parameters['filter']['=XML_ID'] = $parameters['filter']['XML_ID'];
			unset($parameters['filter']['XML_ID']);
		}
		if(isset($parameters['filter']['MODULE_ID']))
		{
			$parameters['filter']['=MODULE_ID'] = $parameters['filter']['MODULE_ID'];
			unset($parameters['filter']['MODULE_ID']);
		}

		return parent::prepareGetListParameters($parameters);
	}
}
