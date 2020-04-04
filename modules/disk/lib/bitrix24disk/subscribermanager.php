<?php

namespace Bitrix\Disk\Bitrix24Disk;

use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Disk\ProxyType;
use Bitrix\Disk\Sharing;
use Bitrix\Disk\Storage;
use Bitrix\Main\Application;

final class SubscriberManager
{
	private $subscribersByObjectId = array();
	/** @var Sharing[] */
	private $sharingsByObject = array();
	/** @var array */
	private $detectCycles = array();

	/**
	 * Collects id of user storage which contains $object.
	 * @param BaseObject $object File or Folder.
	 *
	 * @return array
	 */
	public function collectSubscribers(BaseObject $object)
	{
		if (isset($this->subscribersByObjectId[$object->getId()]))
		{
			return $this->subscribersByObjectId[$object->getId()];
		}

		if (isset($this->detectCycles[$object->getId()]))
		{
			//it's cycle! recursion!
			return array();
		}

		$this->detectCycles[$object->getId()] = true;

		$byParentId = array();
		$subscribers = array();
		if (isset($this->subscribersByObjectId[$object->getParentId()]))
		{
			$subscribers = $this->subscribersByObjectId[$object->getParentId()];
		}
		else
		{
			foreach ($object->getParents(Driver::getInstance()->getFakeSecurityContext()) as $parent)
			{
				if(!$parent instanceof Folder)
				{
					continue;
				}

				$sharingByParent = $this->collectSubscribersBySharings($parent);
				foreach ($sharingByParent as $storageId => $userId)
				{
					$subscribers[$storageId] = $userId;
				}

				//set subscribers to descendants folders
				foreach ($byParentId as $parentId => $data)
				{
					foreach ($sharingByParent as $storageId => $userId)
					{
						$byParentId[$parentId][$storageId] = $userId;
					}
				}

				$byParentId[$parent->getId()] = $sharingByParent;
			}
		}

		$subscribers = $this->appendSubscribersBySharings($object, $subscribers);

		$storage = Storage::loadById($object->getStorageId());
		if ($storage && $storage->getProxyType() instanceof ProxyType\User)
		{
			$subscribers[$storage->getId()] = $storage->getEntityId();

			foreach ($byParentId as $parentId => $data)
			{
				$byParentId[$parentId][$storage->getId()] = $storage->getEntityId();
			}
		}

		foreach ($byParentId as $parentId => $data)
		{
			$this->subscribersByObjectId[$parentId] = $data;
		}

		$this->subscribersByObjectId[$object->getId()] = $subscribers;

		return $subscribers;
	}

	private function collectSubscribersBySharings(BaseObject $object)
	{
		$subscribers = array();
		foreach ($this->getSharingsByObject($object) as $sharing)
		{
			$linkObject = $sharing->getLinkObject();
			if ($linkObject)
			{
				list($type, $id) = Sharing::parseEntityValue($sharing->getToEntity());
				if($type === Sharing::TYPE_TO_USER)
				{
					$subscribers[$linkObject->getStorageId()] = $id;
				}
				foreach ($this->collectSubscribers($linkObject) as $storageId => $userId)
				{
					$subscribers[$storageId] = $userId;
				}
			}
		}

		return $subscribers;
	}

	private function appendSubscribersBySharings(BaseObject $object, array $alreadySubscribers)
	{
		foreach ($this->collectSubscribersBySharings($object) as $storageId => $userId)
		{
			$alreadySubscribers[$storageId] = $userId;
		}

		return $alreadySubscribers;
	}

	/**
	 * Returns sharings by object.
	 *
	 * @param BaseObject $object File or Folder.
	 * @internal
	 * @return Sharing[]
	 */
	public function getSharingsByObject(BaseObject $object)
	{
		if (isset($this->sharingsByObject[$object->getId()]))
		{
			return $this->sharingsByObject[$object->getId()];
		}

		return $object->getSharingsAsReal();
	}

	/**
	 * Preloads sharings for subtree of folder.
	 *
	 * @param Folder $folder Folder.
	 *
	 * @return void
	 */
	public function preloadSharingsForSubtree(Folder $folder)
	{
		$declineStatus = SharingTable::STATUS_IS_DECLINED;
		$userType = SharingTable::TYPE_TO_USER;

		$sharingRowIterator = Application::getConnection()->query("
			SELECT sharing.*, path.OBJECT_ID PATH_OBJECT_ID
			FROM b_disk_object_path path
				LEFT JOIN b_disk_sharing sharing ON sharing.REAL_OBJECT_ID = path.OBJECT_ID
			WHERE 
				(path.PARENT_ID = {$folder->getId()} AND sharing.STATUS <> {$declineStatus} AND sharing.TYPE = {$userType}) OR
				(path.PARENT_ID = {$folder->getId()} AND sharing.ID IS NULL)
		");

		foreach ($sharingRowIterator as $row)
		{
			$objectId = $row['PATH_OBJECT_ID'];
			unset($row['PATH_OBJECT_ID']);

			if (!isset($this->sharingsByObject[$objectId]))
			{
				$this->sharingsByObject[$objectId] = array();
			}

			if (array_filter($row))
			{
				$this->sharingsByObject[$objectId][] = Sharing::buildFromArray($row);
			}
		}
	}

	/**
	 * Cleans runtime cache.
	 *
	 * @return void
	 */
	public function clean()
	{
		$this->subscribersByObjectId = array();
		$this->sharingsByObject = array();
		$this->detectCycles = array();
	}
}