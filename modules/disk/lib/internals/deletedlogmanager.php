<?php

namespace Bitrix\Disk\Internals;

use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;

final class DeletedLogManager
{
	/** @var bool */
	private $isRegisteredShutdownFunction = false;
	/** @var array */
	private $subscribedStorages = array();
	/** @var array */
	private $subscribedUsers = array();
	/** @var array */
	private $logData = array();

	private function registerShutdownFunction()
	{
		if ($this->isRegisteredShutdownFunction)
		{
			return;
		}

		$self = $this;
		register_shutdown_function(function() use ($self){
			$self->finalize();
		});

		$this->isRegisteredShutdownFunction = true;
	}

	public function finalize()
	{
		$this->insertLogData();
		$this->cleanCache();
		$this->notifyUsers();
	}

	public function mark(BaseObject $object, $deletedBy)
	{
		$this->registerShutdownFunction();

		if ($object instanceof Folder)
		{
			$dateTime = new DateTime();

			$subscribers = Driver::getInstance()->collectSubscribers($object);
			foreach($subscribers as $storageId => $userId)
			{
				$this->logData[] = array(
					'STORAGE_ID' => $storageId,
					'OBJECT_ID' => $object->getId(),
					'TYPE' => ObjectTable::TYPE_FOLDER,
					'USER_ID' => $deletedBy,
					'CREATE_TIME' => $dateTime,
				);
			}

			$this->subscribedStorages = array_merge($this->subscribedStorages, array_keys($subscribers));
			$this->subscribedUsers = array_merge($this->subscribedUsers, $subscribers);
		}
		elseif ($object instanceof File)
		{
			$dateTime = new DateTime();

			$subscribers = Driver::getInstance()->collectSubscribers($object);
			foreach($subscribers as $storageId => $userId)
			{
				$this->logData[] = array(
					'STORAGE_ID' => $storageId,
					'OBJECT_ID' => $object->getId(),
					'TYPE' => ObjectTable::TYPE_FILE,
					'USER_ID' => $deletedBy,
					'CREATE_TIME' => $dateTime,
				);
			}

			$this->subscribedUsers = array_merge($this->subscribedUsers, $subscribers);
		}
	}

	private function insertLogData()
	{
		DeletedLogTable::insertBatch($this->logData);

		$this->logData = array();
	}

	private function cleanCache()
	{
		Driver::getInstance()->cleanCacheTreeBitrixDisk(array_unique($this->subscribedStorages));

		$this->subscribedStorages = array();
	}

	private function notifyUsers()
	{
		Driver::getInstance()->sendChangeStatus(array_unique($this->subscribedUsers));
		if (Loader::includeModule('pull'))
		{
			\Bitrix\Pull\Event::send();
		}

		$this->subscribedUsers = array();
	}
}