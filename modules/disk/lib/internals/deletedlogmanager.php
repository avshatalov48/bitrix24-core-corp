<?php

namespace Bitrix\Disk\Internals;

use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Steppers\DeletedLogMover;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;

final class DeletedLogManager
{
	/** @var bool */
	private $isRegisteredFinalizeFunction = false;
	/** @var array */
	private $subscribedStorages = array();
	/** @var array */
	private $subscribedUsers = array();
	/** @var array */
	private $logData = array();

	public function __construct()
	{
		$this->registerFinalizeFunction();
	}

	private function registerFinalizeFunction(): void
	{
		if ($this->isRegisteredFinalizeFunction)
		{
			return;
		}

		Application::getInstance()->addBackgroundJob(function () {
			$this->finalize();
		});

		$this->isRegisteredFinalizeFunction = true;
	}

	/**
	 * @return string|DeletedLogTable|DeletedLogV2Table
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function getLogTable()
	{
		if ($this->isMigrated())
		{
			return DeletedLogV2Table::class;
		}

		return DeletedLogTable::class;
	}

	protected function isMigrated()
	{
		return DeletedLogMover::getStatus() === DeletedLogMover::STATUS_DONE;
	}

	/**
	 * @param array $parameters
	 *
	 * @return \Bitrix\Main\ORM\Query\Result
	 */
	public function getEntries(array $parameters)
	{
		$className = $this->getLogTable();

		return $className::getList($parameters);
	}

	public function finalize()
	{
		$this->insertLogData();
		$this->cleanCache();
		$this->notifyUsers();
	}

	public function mark(BaseObject $object, $deletedBy)
	{
		$objectEvent = $object->makeObjectEvent(
			'objectMarkDeleted',
			[
				'object' => [
					'id' => (int)$object->getId(),
					'deletedBy' => (int)$deletedBy,
				],
			]
		);
		$objectEvent->sendToObjectChannel();

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

	public function markAfterMove(BaseObject $object, array $subscribersLostAccess, $updatedBy)
	{
		$dateTime = new DateTime();
		$isFolder = $object instanceof Folder;
		foreach ($subscribersLostAccess as $storageId => $userId)
		{
			$this->logData[] = [
				'STORAGE_ID' => $storageId,
				'OBJECT_ID' => $object->getId(),
				'TYPE' => $isFolder? ObjectTable::TYPE_FOLDER : ObjectTable::TYPE_FILE,
				'USER_ID' => $updatedBy,
				'CREATE_TIME' => $dateTime,
			];
		}

		if ($isFolder)
		{
			Driver::getInstance()->cleanCacheTreeBitrixDisk(array_keys($subscribersLostAccess));
		}

		Driver::getInstance()->sendChangeStatus($subscribersLostAccess);
	}

	private function insertLogData()
	{
		if (!$this->isMigrated())
		{
			DeletedLogTable::insertBatch($this->logData);
		}

		DeletedLogV2Table::upsertBatch($this->logData);

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
