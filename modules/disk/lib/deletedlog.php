<?php

namespace Bitrix\Disk;

use Bitrix\Disk\Internals\DeletedLogTable;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Main\Type\DateTime;

/**
 * Class DeletedLog
 * @package Bitrix\Disk
 * @internal
 */
final class DeletedLog extends Internals\Model
{
	/** @var int */
	protected $userId;
	/** @var User */
	protected $user;
	/** @var int */
	protected $storageId;
	/** @var int */
	protected $objectId;
	/** @var int */
	protected $type;
	/** @var DateTime */
	protected $createTime;

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getTableClassName()
	{
		return DeletedLogTable::className();
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
	public function getObjectId()
	{
		return $this->objectId;
	}

	/**
	 * @return int
	 */
	public function getStorageId()
	{
		return $this->storageId;
	}

	/**
	 * @return int
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @return User
	 */
	public function getUser()
	{
		if($this->userId === null)
		{
			return null;
		}
		if(SystemUser::isSystemUserId($this->userId))
		{
			return SystemUser::create();
		}

		if(isset($this->user) && $this->userId == $this->user->getId())
		{
			return $this->user;
		}
		$this->user = User::loadById($this->userId);

		return $this->user;
	}

	/**
	 * Create file-entry in deleted log.
	 * It is necessary for a Bitrix24.desktop.
	 * And notify subscribers.
	 * @param File            $file
	 * @param                 $deletedBy
	 * @param ErrorCollection $errorCollection
	 * @return void
	 */
	public static function addFile(File $file, $deletedBy, ErrorCollection $errorCollection)
	{
		Driver::getInstance()->getDeletedLogManager()->mark($file, $deletedBy);
	}

	/**
	 * Create folder-entry in deleted log.
	 * It is necessary for a Bitrix24.desktop.
	 * And notify subscribers.
	 * @param Folder          $folder
	 * @param                 $deletedBy
	 * @param ErrorCollection $errorCollection
	 * @return void
	 */
	public static function addFolder(Folder $folder, $deletedBy, ErrorCollection $errorCollection)
	{
		Driver::getInstance()->getDeletedLogManager()->mark($folder, $deletedBy);
	}

	public static function addAfterMove(BaseObject $object, array $subscribersLostAccess, $updatedBy, ErrorCollection $errorCollection)
	{
		$items = array();
		$dateTime = new DateTime();

		$isFolder = $object instanceof Folder;
		foreach($subscribersLostAccess as $storageId => $userId)
		{
			$items[] = array(
				'STORAGE_ID' => $storageId,
				'OBJECT_ID' => $object->getId(),
				'TYPE' => $isFolder ? ObjectTable::TYPE_FOLDER : ObjectTable::TYPE_FILE,
				'USER_ID' => $updatedBy,
				'CREATE_TIME' => $dateTime,
			);
		}
		unset($storageId, $userId);

		DeletedLogTable::insertBatch($items);

		if($isFolder)
		{
			Driver::getInstance()->cleanCacheTreeBitrixDisk(array_keys($subscribersLostAccess));
		}
		Driver::getInstance()->sendChangeStatus($subscribersLostAccess);
	}

	/**
	 * @return array
	 */
	public static function getMapAttributes()
	{
		return array(
			'ID' => 'id',
			'USER_ID' => 'userId',
			'STORAGE_ID' => 'storageId',
			'OBJECT_ID' => 'objectId',
			'TYPE' => 'type',
			'CREATE_TIME' => 'createTime',
		);
	}
}