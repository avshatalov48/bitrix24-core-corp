<?php

namespace Bitrix\Disk\Document;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\File;
use Bitrix\Disk\Internals\Entity\Model;
use Bitrix\Disk\Internals\TrackedObjectTable;
use Bitrix\Disk\User;
use Bitrix\Main\Error;
use Bitrix\Main\Type\DateTime;

/**
 * @method File getFile()
 * @method File getRealObject()
 * @method AttachedObject getAttachedObject()
 * @method User getUser()
 */
final class TrackedObject extends Model
{
	public const REF_USER = 'user';
	public const REF_OBJECT = 'file';
	public const REF_REAL_OBJECT = 'realObject';
	public const REF_ATTACHED_OBJECT = 'attachedObject';

	/** @var int */
	protected $userId;
	/** @var int */
	protected $objectId;
	/** @var int */
	protected $realObjectId;
	/** @var int */
	protected $attachedObjectId;
	/** @var DateTime */
	protected $createTime;
	/** @var DateTime */
	protected $updateTime;

	public static function getTableClassName()
	{
		return TrackedObjectTable::class;
	}

	public function canRead(int $userId): bool
	{
		if ($this->getFileId())
		{
			$file = $this->getFile();
			if (!$file)
			{
				return false;
			}

			$securityContext = $file->getStorage()->getSecurityContext($userId);
			if ($file->canRead($securityContext))
			{
				return true;
			}
		}

		$attachedObject = $this->getAttachedObject();
		if (!$attachedObject)
		{
			return false;
		}

		return $attachedObject->canRead($userId);
	}

	public function canUpdate(int $userId): bool
	{
		if ($this->getFileId())
		{
			$file = $this->getFile();
			if (!$file)
			{
				return false;
			}

			$securityContext = $file->getStorage()->getSecurityContext($userId);
			if ($file->canUpdate($securityContext))
			{
				return true;
			}
		}

		$attachedObject = $this->getAttachedObject();
		if (!$attachedObject)
		{
			return false;
		}

		return $attachedObject->canUpdate($userId);
	}

	public function canRename(int $userId): bool
	{
		if ($this->getFileId())
		{
			$file = $this->getFile();
			if (!$file)
			{
				return false;
			}

			$securityContext = $file->getStorage()->getSecurityContext($userId);
			if ($file->canRename($securityContext))
			{
				return true;
			}
		}

		return false;
	}

	public function canMarkDeleted(int $userId): bool
	{
		if (!$this->getFileId())
		{
			return false;
		}

		$file = $this->getFile();
		if (!$file)
		{
			return false;
		}

		$securityContext = $file->getStorage()->getSecurityContext($userId);

		return $file->canMarkDeleted($securityContext);
	}

	public function canShare(int $userId): bool
	{
		if ($this->getFileId())
		{
			$file = $this->getFile();
			if (!$file)
			{
				return false;
			}

			$securityContext = $file->getStorage()->getSecurityContext($userId);
			if ($file->canShare($securityContext))
			{
				return true;
			}
		}

		return false;
	}

	public function canChangeRights(int $userId): bool
	{
		if ($this->getFileId())
		{
			$file = $this->getFile();
			if (!$file)
			{
				return false;
			}

			$securityContext = $file->getStorage()->getSecurityContext($userId);
			if ($file->canChangeRights($securityContext))
			{
				return true;
			}
		}

		return false;
	}

	public function delete(): bool
	{
		return $this->deleteInternal();
	}

	public function getFileId(): int
	{
		return $this->getObjectId();
	}

	public function getObjectId(): int
	{
		return $this->objectId;
	}

	public function getRealObjectId(): int
	{
		return $this->realObjectId;
	}

	public function getAttachedObjectId(): ?int
	{
		return $this->attachedObjectId;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getCreateTime(): DateTime
	{
		return $this->createTime;
	}

	public function getUpdateTime(): DateTime
	{
		return $this->updateTime;
	}

	/**
	 * Sets object to setup session.
	 * @param BaseObject $object Object.
	 *
	 * @return $this
	 */
	public function setObject(BaseObject $object)
	{
		$this->setReferenceValue(self::REF_OBJECT, $object);

		return $this;
	}

	/**
	 * @return array
	 */
	public static function getMapAttributes()
	{
		return [
			'ID' => 'id',
			'USER_ID' => 'userId',
			'OBJECT_ID' => 'objectId',
			'REAL_OBJECT_ID' => 'realObjectId',
			'ATTACHED_OBJECT_ID' => 'attachedObjectId',
			'CREATE_TIME' => 'createTime',
			'UPDATE_TIME' => 'updateTime',
		];
	}

	/**
	 * Returns the list attributes which is connected with another models.
	 *
	 * @return array
	 */
	public static function getMapReferenceAttributes()
	{
		return [
			self::REF_USER => [
				'class' => User::class,
				'select' => User::getFieldsForSelect(),
				'load' => function(self $trackedObject){
					return User::loadById($trackedObject->getUserId());
				},
			],
			self::REF_OBJECT => [
				'class' => File::class,
				'load' => function(self $trackedObject){
					return BaseObject::loadById($trackedObject->getObjectId());
				},
			],
			self::REF_REAL_OBJECT => [
				'class' => File::class,
				'load' => function(self $trackedObject){
					if ($trackedObject->getRealObjectId() === $trackedObject->getFileId())
					{
						return $trackedObject->getFile();
					}

					return BaseObject::loadById($trackedObject->getObjectId());
				},
			],
			self::REF_ATTACHED_OBJECT => [
				'class' => AttachedObject::class,
				'load' => function(self $trackedObject){
					return AttachedObject::loadById($trackedObject->getAttachedObjectId());
				},
			],
		];
	}
}