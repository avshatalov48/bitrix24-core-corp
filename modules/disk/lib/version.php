<?php


namespace Bitrix\Disk;


use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\VersionTable;
use Bitrix\Disk\View\VersionViewManager;
use Bitrix\Main\Event;
use Bitrix\Main\Type\DateTime;

final class Version extends Internals\Model
{
	public const ERROR_COULD_NOT_CREATE_NEW_FILE = 'DISK_VERSION_22002';

	/** @var int */
	protected $objectId;
	/** @var BaseObject */
	protected $object;
	/** @var int */
	protected $fileId;
	/** @var int */
	protected $size;
	/** @var array */
	protected $file;
	/** @var string */
	protected $name;
	/** @var string */
	protected $extension;
	/** @var string */
	protected $miscData;
	/** @var array */
	protected $unserializeData;
	/** @var DateTime */
	protected $objectCreateTime;
	/** @var int */
	protected $objectCreatedBy;
	/** @var DateTime */
	protected $objectUpdateTime;
	/** @var int  */
	protected $objectUpdatedBy;
	/** @var int  */
	protected $globalContentVersion;


	/** @var DateTime */
	protected $createTime;
	/** @var int */
	protected $createdBy;
	/** @var  User */
	protected $createUser;
	/** @var int */
	protected $viewId;
	/** @var View\Base */
	protected $view;

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getTableClassName()
	{
		return VersionTable::className();
	}

	public static function add(array $data, ErrorCollection $errorCollection)
	{
		$model = parent::add($data, $errorCollection);

		if($model)
		{
			$event = new Event(Driver::INTERNAL_MODULE_ID, "onAfterAddVersion", array($model));
			$event->send();
		}

		return $model;
	}

	/**
	 * @return DateTime
	 */
	public function getCreateTime()
	{
		return $this->createTime;
	}

	/**
	 * @return int
	 */
	public function getCreatedBy()
	{
		return $this->createdBy;
	}

	/**
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
	 * @return int
	 */
	public function getFileId()
	{
		return $this->fileId;
	}

	/**
	 * @return array|null
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public function getFile()
	{
		if(!$this->fileId)
		{
			return null;
		}

		if(isset($this->file) && $this->fileId == $this->file['ID'])
		{
			return $this->file;
		}

		$this->file = \CFile::GetFileArray($this->fileId);

		if(!$this->file)
		{
			return array();
		}

		return $this->file;
	}

	public function createNewFile(Folder $targetFolder, int $createdBy, bool $generateUniqueName = false): ?File
	{
		$this->errorCollection->clear();

		$forkFileId = \CFile::CloneFile($this->getFileId());
		if (!$forkFileId)
		{
			$this->errorCollection[] = new Error('Could not copy file.', self::ERROR_COULD_NOT_CREATE_NEW_FILE);

			return null;
		}

		$newFile = $targetFolder->addFile([
			'NAME' => $this->getName(),
			'FILE_ID' => $forkFileId,
			'SIZE' => $this->getSize(),
			'CREATED_BY' => $createdBy,
		], [], $generateUniqueName);

		if (!$newFile)
		{
			\CFile::delete($forkFileId);
			$this->errorCollection->add($targetFolder->getErrors());

			return null;
		}

		return $newFile;
	}

	/**
	 * Returns id of view.
	 * @return int|null
	 */
	public function getViewId()
	{
		return $this->viewId;
	}

	/**
	 * Set viewId, save in the database.
	 *
	 * @param int $fileId
	 * @return bool
	 */
	public function changeViewId($fileId)
	{
		return $this->update(array('VIEW_ID' => $fileId));
	}

	/**
	 * Delete converted view file.
	 *
	 * @return bool
	 */
	public function deleteView()
	{
		if($this->viewId > 0)
		{
			\CFile::Delete($this->viewId);
			return $this->update(array('VIEW_ID' => null));
		}

		return false;
	}

	/**
	 * @return int
	 */
	public function getGlobalContentVersion()
	{
		return $this->globalContentVersion;
	}

	/**
	 * @return DateTime
	 */
	public function getObjectCreateTime()
	{
		return $this->objectCreateTime;
	}

	/**
	 * @return int
	 */
	public function getObjectCreatedBy()
	{
		return $this->objectCreatedBy;
	}

	/**
	 * @return DateTime
	 */
	public function getObjectUpdateTime()
	{
		return $this->objectUpdateTime;
	}

	/**
	 * @return int
	 */
	public function getObjectUpdatedBy()
	{
		return $this->objectUpdatedBy;
	}

	/**
	 * @return int
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * @return string
	 */
	public function getMiscData()
	{
		return $this->miscData;
	}

	/**
	 * @return array
	 */
	public function getUnserializeMiscData()
	{
		if(isset($this->unserializeData))
		{
			return $this->unserializeData;
		}

		if(is_string($this->miscData))
		{
			$this->unserializeData = @unserialize($this->miscData, ['allowed_classes' => false]);
			if($this->unserializeData === false)
			{
				return array();
			}
		}

		return $this->unserializeData;
	}

	public function getMiscDataByKey($key)
	{
		if(!isset($this->unserializeData))
		{
			$this->getUnserializeMiscData();
		}

		return isset($this->unserializeData[$key])? $this->unserializeData[$key] : null;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	public function getExtension()
	{
		if($this->extension === null)
		{
			$this->extension = getFileExtension($this->getName());
		}
		return $this->extension;
	}

	/**
	 * @return int
	 */
	public function getObjectId()
	{
		return $this->objectId;
	}

	/**
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
	 * Join data from another version.
	 * @param $data
	 * @return bool
	 * @internal
	 */
	public function joinData(array $data)
	{
		return $this->update(array_intersect_key($data, array(
			'CREATE_TIME' => true,

			'FILE_ID' => true,
			'SIZE' => true,

			'GLOBAL_CONTENT_VERSION' => true,

			'OBJECT_CREATED_BY' => true,
			'OBJECT_UPDATED_BY' => true,

			'OBJECT_CREATE_TIME'=> true,
			'OBJECT_UPDATE_TIME'=> true,

			'VIEW_ID' => true,
		)));
	}

	/**
	 * Returns true if the version is head for the file.
	 *
	 * @return bool
	 */
	public function isHead()
	{
		$file = $this->getObject();
		if(!$file)
		{
			return false;
		}

		return $this->getFileId() == $file->getFileId();
	}

	/**
	 * @return Version
	 */
	private function getPrevious()
	{
		$versions = static::getModelList(array(
			'filter' => array(
				'OBJECT_ID' => $this->objectId,
				'<ID' => $this->id,
			),
			'order' => array(
				'ID' => 'DESC',
			),
			'limit' => 1
		));
		return array_shift($versions)?: null;
	}

	/**
	 * Deletes version.
	 *
	 * @param int $deletedBy Id of user.
	 * @return bool
	 */
	public function delete($deletedBy)
	{
		$success = parent::deleteInternal();
		if(!$success)
		{
			return false;
		}
		\CFile::delete($this->fileId);
		if ($this->viewId)
		{
			\CFile::delete($this->viewId);
		}

		$file = $this->getObject();
		if($file && $file->getCurrentState() !== $file::STATE_DELETE_PROCESS && $this->isHead())
		{
			$previous = $this->getPrevious();
			if($previous)
			{
				if($file->updateContent(array(
						'ID' => $previous->getFileId(),
						'FILE_SIZE' => $previous->getSize(),
					), $deletedBy)
				)
				{
					$previous->update(array(
						'GLOBAL_CONTENT_VERSION' => $file->getGlobalContentVersion()
					));
				}
			}
		}

		return true;
	}

	/**
	 * @return array
	 */
	public static function getMapAttributes()
	{
		return array(
			'ID' => 'id',
			'OBJECT_ID' => 'objectId',
			'OBJECT' => 'object',
			'FILE_ID' => 'fileId',
			'SIZE' => 'size',
			'NAME' => 'name',
			'MISC_DATA' => 'miscData',

			'OBJECT_CREATE_TIME' => 'objectCreateTime',
			'OBJECT_CREATED_BY' => 'objectCreatedBy',
			'OBJECT_UPDATE_TIME' => 'objectUpdateTime',
			'OBJECT_UPDATED_BY' => 'objectUpdatedBy',
			'GLOBAL_CONTENT_VERSION' => 'globalContentVersion',

			'CREATE_TIME' => 'createTime',
			'CREATED_BY' => 'createdBy',
			'CREATE_USER' => 'createUser',
			'VIEW_ID' => 'viewId',
		);
	}

	/**
	 * @return array
	 */
	public static function getMapReferenceAttributes()
	{
		return array(
			'CREATE_USER' => array(
				'class' => User::className(),
				'select' => User::getFieldsForSelect(),
			),
			'OBJECT' => File::className(),
		);
	}

	/**
	 * Return instance of View for current version.
	 *
	 * @return View\Base
	 */
	public function getView()
	{
		if(!$this->view)
		{
			$isTransformationEnabledInStorage = true;
			$storage = $this->getObject()->getStorage();
			if($storage)
			{
				$isTransformationEnabledInStorage = $storage->isEnabledTransformation();
			}
			if(TypeFile::isDocument($this->name))
			{
				$this->view = new View\Document($this->getName(), $this->getFileId(), $this->getViewId(), $isTransformationEnabledInStorage);
			}
			elseif(TypeFile::isVideo($this->name))
			{
				$this->view = new View\Video($this->getName(), $this->getFileId(), $this->getViewId(), $isTransformationEnabledInStorage);
			}
			else
			{
				$this->view = new View\Base($this->getName(), $this->getFileId(), $this->getViewId(), $isTransformationEnabledInStorage);
			}
		}

		return $this->view;
	}
}