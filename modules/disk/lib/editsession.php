<?php


namespace Bitrix\Disk;

use Bitrix\Disk\Internals\EditSessionTable;
use Bitrix\Main\Type\DateTime;

final class EditSession extends Internals\Model
{
	/** @var int */
	protected $objectId;
	/** @var File */
	protected $object;
	/** @var int */
	protected $versionId;
	/** @var Version */
	protected $version;
	/** @var int */
	protected $userId;
	/** @var  User */
	protected $user;
	/** @var int */
	protected $ownerId;
	/** @var  User */
	protected $owner;
	/** @var  bool */
	protected $isExclusive;
	/** @var string */
	protected $service;
	/** @var string */
	protected $serviceFileId;
	/** @var string */
	protected $serviceFileLink;
	/** @var DateTime */
	protected $createTime;

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getTableClassName()
	{
		return EditSessionTable::className();
	}

	/**
	 * @return DateTime
	 */
	public function getCreateTime()
	{
		return $this->createTime;
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
	 * @return int
	 */
	public function getObjectId()
	{
		return $this->objectId;
	}

	/**
	 * @return int
	 */
	public function getVersionId()
	{
		return $this->versionId;
	}

	/**
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
	 * @return User|null
	 */
	public function getOwner()
	{
		if(!$this->ownerId)
		{
			return null;
		}

		if(isset($this->owner) && $this->ownerId == $this->owner->getId())
		{
			return $this->owner;
		}
		$this->owner = User::loadById($this->ownerId);

		return $this->owner;
	}

	/**
	 * @return int
	 */
	public function getOwnerId()
	{
		return $this->ownerId;
	}

	/**
	 * @return string
	 */
	public function getService()
	{
		return $this->service;
	}

	/**
	 * @return string
	 */
	public function getServiceFileId()
	{
		return $this->serviceFileId;
	}

	/**
	 * @return string
	 */
	public function getServiceFileLink()
	{
		return $this->serviceFileLink;
	}

	/**
	 * @return User|null
	 */
	public function getUser()
	{
		if(!$this->userId)
		{
			return null;
		}

		if(isset($this->user) && $this->userId == $this->user->getId())
		{
			return $this->user;
		}
		$this->user = User::loadById($this->userId);

		return $this->user;
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @return bool
	 */
	public function isExclusive()
	{
		return !empty($this->isExclusive);
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
			'VERSION_ID' => 'versionId',
			'VERSION' => 'version',
			'USER_ID' => 'userId',
			'USER' => 'user',
			'OWNER_ID' => 'ownerId',
			'OWNER' => 'owner',
			'IS_EXCLUSIVE' => 'isExclusive',
			'SERVICE' => 'service',
			'SERVICE_FILE_ID' => 'serviceFileId',
			'SERVICE_FILE_LINK' => 'serviceFileLink',
			'CREATE_TIME' => 'createTime',
		);
	}

	/**
	 * @return array
	 */
	public static function getMapReferenceAttributes()
	{
		$userClassName = User::className();
		$fields = User::getFieldsForSelect();

		return array(
			'OBJECT' => File::className(),
			'VERSION' => Version::className(),
			'USER' => array(
				'class' => $userClassName,
				'select' => $fields,
			),
			'OWNER' => array(
				'class' => $userClassName,
				'select' => $fields,
			),
		);
	}
}