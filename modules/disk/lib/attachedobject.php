<?php


namespace Bitrix\Disk;

use Bitrix\Disk\Integration\TransformerManager;
use Bitrix\Disk\Internals\AttachedObjectTable;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\TrackedObjectTable;
use Bitrix\Disk\Uf\Connector;
use Bitrix\Disk\Uf\StubConnector;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;

final class AttachedObject extends Internals\Model
{
	/** @var int */
	protected $objectId;
	/** @var File */
	protected $object;
	/** @var int */
	protected $versionId;
	/** @var Version */
	protected $version;
	/** @var bool */
	protected $isEditable;
	/** @var bool */
	protected $allowEdit;
	/** @var bool */
	protected $allowAutoComment;
	/** @var string */
	protected $moduleId;
	/** @var string */
	protected $entityType;
	/** @var string */
	protected $entityId;
	/** @var DateTime */
	protected $createTime;
	/** @var int */
	protected $createdBy;
	/** @var User */
	protected $createUser;
	/** @var Connector */
	protected $connector;

	protected $operableEntity = array();

	/** @var array */
	private static $storedData = array();

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getTableClassName()
	{
		return AttachedObjectTable::className();
	}

	/**
	 * Checks rights to read current attached object.
	 * @param int $userId Id of user.
	 * @return bool
	 * @throws SystemException
	 */
	public function canRead($userId)
	{
		$connector = $this->getConnector();
		if($connector->canConfidenceReadInOperableEntity() && $this->isConfidenceToOperableEntity())
		{
			return true;
		}

		if (
			!$userId
			&& !$this->getConnector()->isAnonymousAllowed()
		)
		{
			return false;
		}

		return $this->getConnector()->canRead($userId);
	}

	/**
	 * Stores data by object id.
	 * @param int $objectId Id of object.
	 * @param mixed $data Data to store.
	 * @internal
	 * @return void
	 */
	public static function storeDataByObjectId($objectId, $data)
	{
		self::$storedData[$objectId] = $data;
	}

	/**
	 * Returns stored data by object id.
	 * @param int $objectId Id of object.
	 * @return null|mixed
	 * @internal
	 */
	public static function getStoredDataByObjectId($objectId)
	{
		return isset(self::$storedData[$objectId])? self::$storedData[$objectId] : null;
	}

	/**
	 * Checks rights to update current attached object.
	 * @param int $userId Id of user.
	 * @return bool
	 * @throws SystemException
	 */
	public function canUpdate($userId)
	{
		//if attachedObject is version we don't know about self-rights and have to ask head-version (object) about canUpdate. But we don't make it (so expensive). Hack
		if($this->isSpecificVersion() && $this->createdBy && $this->createdBy == $userId)
		{
			return true;
		}
		if(!$this->isEditable)
		{
			return false;
		}
		//this is compatible mode after migrate from webdav with old uf attaches
		if($this->isEditable == 2 && !$this->getStubConnector()->canUpdate($userId))
		{
			return false;
		}

		//If user who attached object to entity had edit operation, then we will not ask Connector about rights (update)
		if($this->createdBy && $this->createdBy == $userId)
		{
			return true;
		}

		if(!$this->allowEdit)
		{
			return false;
		}

		$connector = $this->getConnector();
		if($connector->canConfidenceUpdateInOperableEntity() && $this->isConfidenceToOperableEntity())
		{
			return true;
		}

		//If user allow to edit object in entity - we ask entity about access $userId
		return $connector->canUpdate($userId);
	}

	public function canLock($userId)
	{
		return $this->canUpdate($userId);
	}

	public function canUnlock($userId)
	{
		return $this->canUpdate($userId);
	}

	/**
	 * Sets operable entity.
	 * Need to optimize work in components disk.uf.file, disk.uf.version.
	 * @param array $entityData Entity data.
	 * @return void
	 */
	public function setOperableEntity(array $entityData)
	{
		$this->operableEntity = $entityData;
	}

	protected function isConfidenceToOperableEntity()
	{
		if(!$this->operableEntity)
		{
			return false;
		}
		$connector = $this->getConnector();
		list($operableConnectorClass, ) = Driver::getInstance()->getUserFieldManager()->getConnectorDataByEntityType($this->operableEntity['ENTITY_ID']);
		$confidenceToOperableEntity =
			($this->operableEntity['ENTITY_VALUE_ID'] == $this->entityId) &&
			($operableConnectorClass === $connector::className())
		;

		return $confidenceToOperableEntity;
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
		static::checkRequiredInputParams($data, array(
			'OBJECT_ID', 'ENTITY_ID', 'ENTITY_TYPE', 'MODULE_ID'
		));

		$model = parent::add($data, $errorCollection);
		if($model && $model->getCreatedBy())
		{
			$driver = Driver::getInstance();
			$driver->getRecentlyUsedManager()->push(
				$model->getCreatedBy(),
				$model
			);

			/** @var AttachedObject $model */
			/** @var File $file */
			$file = $model->getObject();
			if($file && TypeFile::isVideo($file))
			{
				if(Loader::includeModule('transformer'))
				{
					TransformerManager::transformToView($file);
				}
//				$transformerManager = new TransformerManager();
//				if($transformerManager->isAvailable())
//				{
//					$transformerManager->transform($file->getFileId());
//				}
			}
		}

		return $model;
	}

	/**
	 * Detaches attached object.
	 * Alias delete().
	 * @return bool
	 */
	public function detach()
	{
		return $this->delete();
	}

	/**
	 * Returns create time.
	 * @return DateTime
	 */
	public function getCreateTime()
	{
		return $this->createTime;
	}

	/**
	 * Returns id of user, who created object.
	 * @return int
	 */
	public function getCreatedBy()
	{
		return $this->createdBy;
	}

	/**
	 * Returns user model, who created object.
	 * @return User
	 */
	public function getCreateUser()
	{
		if(isset($this->createUser) && $this->createdBy == $this->createUser->getId())
		{
			return $this->createUser;
		}
		$this->createUser = User::getModelForReferenceField($this->createdBy, $this->createUser);

		return $this->createUser;
	}

	/**
	 * Returns entity id.
	 * @return string
	 */
	public function getEntityId()
	{
		return $this->entityId;
	}

	/**
	 * Returns entity type.
	 * @return string
	 */
	public function getEntityType()
	{
		return $this->entityType;
	}

	/**
	 * Tells if the attached object is editable.
	 * @return boolean
	 */
	public function isEditable()
	{
		return $this->isEditable;
	}

	/**
	 * Returns value of allow edit property.
	 * @return boolean
	 */
	public function getAllowEdit()
	{
		return $this->allowEdit;
	}

	public function changeAllowEdit(bool $allowEdit)
	{
		return $this->update(['ALLOW_EDIT' => $allowEdit ? 1 : 0]);
	}

	/**
	 * Returns value of allow auto comment property.
	 * @return boolean
	 */
	public function getAllowAutoComment()
	{
		return $this->allowAutoComment;
	}

	/**
	 * Returns module id.
	 * @return string
	 */
	public function getModuleId()
	{
		return $this->moduleId;
	}

	/**
	 * Returns file model.
	 * @see getFile()
	 * @return File|null
	 */
	public function getObject()
	{
		if(!$this->objectId)
		{
			return null;
		}

		if(isset($this->object) && $this->objectId == $this->object->getId())
		{
			return $this->object;
		}
		$this->object = File::loadById($this->objectId);

		return $this->object;
	}

	/**
	 * Returns file model.
	 * @return File|null
	 */
	public function getFile()
	{
		return $this->getObject();
	}

	/**
	 * Returns id of object which was attached.
	 * @return int
	 */
	public function getObjectId()
	{
		return $this->objectId;
	}

	/**
	 * Returns id of version.
	 * @see isSpecificVersion()
	 * @return int
	 */
	public function getVersionId()
	{
		return $this->versionId;
	}

	/**
	 * Returns version model.
	 * @see isSpecificVersion()
	 * @return Version|null
	 */
	public function getVersion()
	{
		if(!$this->versionId)
		{
			return null;
		}

		if(isset($this->version) && $this->versionId == $this->version->getId())
		{
			return $this->version;
		}
		$this->version = Version::loadById($this->versionId);

		return $this->version;
	}

	/**
	 * Tells if the object created by version.
	 * @return bool
	 */
	public function isSpecificVersion()
	{
		return isset($this->versionId);
	}

	public function getName()
	{
		if ($this->isSpecificVersion())
		{
			$version = $this->getVersion();

			return $version? $version->getName() : '';
		}

		$file = $this->getFile();

		return $file? $file->getName() : '';
	}

	/**
	 * Returns file id of attached object. If attached object is version, then it's file id of this version.
	 * @return int|null
	 */
	public function getFileId()
	{
		if ($this->isSpecificVersion())
		{
			$version = $this->getVersion();

			return $version? $version->getFileId() : null;
		}

		$file = $this->getFile();

		return $file? $file->getFileId() : null;
	}

	/**
	 * Returns connector instance for attached object.
	 * @return Connector|null
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getConnector()
	{
		if($this->connector === null)
		{
			$this->connector = Connector::buildFromAttachedObject($this);
		}
		return $this->connector;
	}

	/**
	 * Detaches attached objects by filter.
	 * @param array $filter Filter.
	 * @return void
	 */
	public static function detachByFilter(array $filter)
	{
		if(!$filter)
		{
			return;
		}
		foreach(static::getModelList(array('filter' => $filter)) as $attachedObject)
		{
			$attachedObject->delete();
		}
		unset($attachedObject);
	}

	/**
	 * Deletes model.
	 * @return bool
	 */
	public function delete()
	{
		$success = $this->deleteInternal();

		if(!$success)
		{
			return false;
		}

		TrackedObjectTable::deleteBatch([
			'ATTACHED_OBJECT_ID' => $this->id,
		]);

		if($this->isSpecificVersion())
		{
			return false;
		}

		$file = $this->getFile();

		if(
			$file &&
			$file->getGlobalContentVersion() == 1 &&
			$file->countAttachedObjects() == 0 &&
			$file->getParent() &&
			$file->getParent()->getCode() === Folder::CODE_FOR_UPLOADED_FILES
		)
		{
			$file->delete(SystemUser::SYSTEM_USER_ID);
		}

		return $success;
	}

	/**
	 * Disables auto comments for attached object.
	 * @return bool
	 */
	public function disableAutoComment()
	{
		if(!$this->allowAutoComment)
		{
			return true;
		}
		return $this->update(array('ALLOW_AUTO_COMMENT' => 0));
	}

	/**
	 * Enables auto comments for attached object.
	 * @return bool
	 */
	public function enableAutoComment()
	{
		if($this->allowAutoComment)
		{
			return true;
		}
		return $this->update(array('ALLOW_AUTO_COMMENT' => 1));
	}

	/**
	 * Returns the list of pair for mapping data and object properties.
	 * Key is field in DataManager, value is object property.
	 * @return array
	 */
	public static function getMapAttributes()
	{
		return array(
			'ID' => 'id',
			'OBJECT_ID' => 'objectId',
			'OBJECT' => 'object',
			'VERSION_ID' => 'versionId',
			'VERSION' => 'version',
			'IS_EDITABLE' => 'isEditable',
			'ALLOW_EDIT' => 'allowEdit',
			'ALLOW_AUTO_COMMENT' => 'allowAutoComment',
			'MODULE_ID' => 'moduleId',
			'ENTITY_TYPE' => 'entityType',
			'ENTITY_ID' => 'entityId',
			'CREATE_TIME' => 'createTime',
			'CREATED_BY' => 'createdBy',
		);
	}

	/**
	 * Returns the list attributes which is connected with another models.
	 * @return array
	 */
	public static function getMapReferenceAttributes()
	{
		return array(
			'OBJECT' => File::className(),
			'VERSION' => Version::className(),
		);
	}

	protected function getStubConnector()
	{
		/** @var \Bitrix\Disk\Uf\Connector $connector */
		$stubConnector = new StubConnector($this->getEntityId());
		$stubConnector->setAttachedObject($this);

		return $stubConnector;
	}
} 