<?php


namespace Bitrix\Disk;

use Bitrix\Disk\Document;
use Bitrix\Main\Type\DateTime;

final class ObjectLock extends Internals\Model
{
	/** @var string */
	protected $token;
	/** @var int */
	protected $objectId;
	/** @var BaseObject */
	protected $object;
	/** @var int */
	protected $createdBy;
	/** @var  User */
	protected $createUser;
	/** @var DateTime */
	protected $createTime;
	/** @var DateTime */
	protected $expiryTime;
	/** @var int */
	protected $type;
	/** @var int */
	protected $isExclusive;

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getTableClassName()
	{
		return Internals\ObjectLockTable::className();
	}

	/**
	 * Returns create time.
	 *
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
	 * Returns object.
	 *
	 * @return BaseObject|null
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
		$this->object = BaseObject::loadById($this->objectId);

		return $this->object;
	}

	/**
	 * Returns object id.
	 *
	 * @return int
	 */
	public function getObjectId()
	{
		return $this->objectId;
	}

	/**
	 * @return string
	 */
	public function getToken()
	{
		return $this->token;
	}

	/**
	 * @return DateTime
	 */
	public function getExpiryTime()
	{
		return $this->expiryTime;
	}

	/**
	 * @return int
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return bool
	 */
	public function isExclusive()
	{
		return (bool)$this->isExclusive;
	}

	public function delete($deletedBy)
	{
		return $this->deleteInternal();
	}

	/**
	 * Generates lock token.
	 * 
	 * @see \CDavWebDavServer::getNewLockToken
	 * @return string
	 */
	public static function generateLockToken()
	{
		if (function_exists('uuid_create'))
		{
			return uuid_create();
		}
		else
		{
			$uuid = md5(microtime().getmypid());

			$uuid{12} = '4';
			$n = 8 + (ord($uuid{16}) & 3);
			$hex = '0123456789abcdef';
			$uuid{16} = substr($hex, $n, 1);

			return substr($uuid,  0, 8).'-'.
				substr($uuid,  8, 4).'-'.
				substr($uuid, 12, 4).'-'.
				substr($uuid, 16, 4).'-'.
				substr($uuid, 20);
		}
	}

	/**
	 * Returns true if user can unlock object.
	 *
	 * @param int $userId Id of user who wants to unlock object.
	 * @return bool
	 */
	public function canUnlock($userId)
	{
		if($this->getCreatedBy() == $userId)
		{
			return true;
		}

		if(SystemUser::isSystemUserId($userId))
		{
			return true;
		}

		$user = User::loadById($userId);
		if(!$user)
		{
			return false;
		}

		if($user->isAdmin())
		{
			return true;
		}

		return false;
	}

	/**
	 * Returns the list of pair for mapping data and object properties.
	 * Key is field in DataManager, value is object property.
	 *
	 * @return array
	 */
	public static function getMapAttributes()
	{
		return array(
			'ID' => 'id',
			'TOKEN' => 'token',
			'OBJECT_ID' => 'objectId',
			'OBJECT' => 'object',
			'CREATED_BY' => 'createdBy',
			'CREATE_TIME' => 'createTime',
			'EXPIRY_TIME' => 'expiryTime',
			'TYPE' => 'type',
			'IS_EXCLUSIVE' => 'isExclusive',
		);
	}

	/**
	 * Returns the list attributes which is connected with another models.
	 *
	 * @return array
	 */
	public static function getMapReferenceAttributes()
	{
		$userClassName = User::className();
		$fields = User::getFieldsForSelect();

		return array(
			'OBJECT' => BaseObject::className(),
			'CREATE_USER' => array(
				'class' => $userClassName,
				'select' => $fields,
			),
		);
	}
}