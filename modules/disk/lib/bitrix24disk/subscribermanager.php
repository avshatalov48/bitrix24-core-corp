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
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\Type\Collection;

final class SubscriberManager
{
	const DIRECTION_PARENTS = 0;
	const DIRECTION_SUBTREE = 1;

	private $subscribersByObjectId = array();
	/** @var Sharing[] */
	private $sharingsByObject = array();
	/** @var array */
	private $detectCycles = array();

	public function __construct()
	{
		$this->setEvents();
	}

	protected function setEvents()
	{
		$eventManager = EventManager::getInstance();

		$eventManager->addEventHandler(Driver::INTERNAL_MODULE_ID, 'FileOnAfterMove', array($this, 'onObjectOnAfterMove'));
		$eventManager->addEventHandler(Driver::INTERNAL_MODULE_ID, 'FolderOnAfterMove', array($this, 'onObjectOnAfterMove'));
		$eventManager->addEventHandler(Driver::INTERNAL_MODULE_ID, 'ObjectOnAfterMove', array($this, 'onObjectOnAfterMove'));
		$eventManager->addEventHandler(Driver::INTERNAL_MODULE_ID, 'SharingOnAfterUpdate', array($this, 'onSharingUpdated'));
	}

	public function onObjectOnAfterMove(Event $event)
	{
		$primaryData = $event->getParameter('id');
		if ($primaryData)
		{
			$this->cleanByObjectId($primaryData['ID']);
		}
	}

	public function onSharingUpdated(Event $event)
	{
		$fields = $event->getParameter('fields');
		if ($fields['REAL_OBJECT_ID'])
		{
			$this->cleanByObjectId($fields['REAL_OBJECT_ID']);
		}
		if ($fields['LINK_OBJECT_ID'])
		{
			$this->cleanByObjectId($fields['LINK_OBJECT_ID']);
		}
	}

	protected function cleanByObjectId($objectId)
	{
		unset($this->subscribersByObjectId[$objectId]);
		unset($this->detectCycles[$objectId]);
		unset($this->sharingsByObject[$objectId]);
	}

	public function collectSubscribersFromSubtree(BaseObject $object)
	{
		return $this->collectSubscribersGeneralWay($object, self::DIRECTION_SUBTREE);
	}

	protected function collectSubscribersGeneralWay(BaseObject $object, $direction)
	{
		set_time_limit(0);

		if (!in_array($direction, [self::DIRECTION_PARENTS, self::DIRECTION_SUBTREE], true))
		{
			throw new ArgumentException('Invalid argument value for', 'direction');
		}

		$maxInnerJoinDepth = 12;
		$currentDepth = 0;
		$emptySelect = false;
		$connection = Application::getConnection();
		$objectId = (int)$object->getId();
		$sharingStatus = Sharing::STATUS_IS_DECLINED;

		$ownerStorageIds = [];
		$subscribers = [];
		$where = "
			WHERE
				path." . ($direction === self::DIRECTION_PARENTS? 'OBJECT_ID' : 'PARENT_ID') . " = {$objectId} AND sharing.STATUS <> {$sharingStatus}							
		";
		$pathSearchField = $direction === self::DIRECTION_PARENTS ? 'PARENT_ID' : 'OBJECT_ID';

		$sliceHashes = [];
		while ($currentDepth < $maxInnerJoinDepth && !$emptySelect)
		{
			$sharingTableAlias = "sharing" . ($currentDepth - 1 >= 0 ? $currentDepth - 1 : '');

			$query = "
				SELECT DISTINCT {$sharingTableAlias}.ID, {$sharingTableAlias}.TO_ENTITY, {$sharingTableAlias}.LINK_STORAGE_ID, {$sharingTableAlias}.REAL_STORAGE_ID 
				FROM b_disk_sharing {$sharingTableAlias}
			";

			if ($currentDepth === 0)
			{
				$query .= "
					INNER JOIN b_disk_object_path path ON sharing.REAL_OBJECT_ID = path.{$pathSearchField}
				";
				$query .= $where;
			}
			else
			{
				$pathTableAlias = "path" . ($currentDepth - 1 >= 0 ? $currentDepth - 1 : '');

				$inner = '';
				for ($i = 0; $i < $currentDepth; $i++)
				{
					$prevI = ($i - 1 >= 0) ? ($i - 1) : '';

					$prevPathField = 'PARENT_ID';
					if ($prevI === '')
					{
						$prevPathField = $pathSearchField;
					}

					$inner .= " 
						INNER JOIN b_disk_sharing sharing{$prevI} ON sharing{$prevI}.REAL_OBJECT_ID = path{$prevI}.{$prevPathField}
       					INNER JOIN b_disk_object_path path{$i} ON path{$i}.OBJECT_ID = sharing{$prevI}.LINK_OBJECT_ID
					";
				}

				$whereWithSubQuery = "
					WHERE {$sharingTableAlias}.REAL_OBJECT_ID IN (
						SELECT {$pathTableAlias}.PARENT_ID
						FROM b_disk_object_path path
						{$inner}
						{$where}
				)";

				$query .= $whereWithSubQuery;
			}

			$emptySelect = true;

			$rows = $connection->query($query)->fetchAll();
			Collection::sortByColumn($rows, 'ID');

			$ids = [];
			foreach ($rows as $row)
			{
				$ids[] = $row['ID'];

				$emptySelect = false;
				[$type, $id] = Sharing::parseEntityValue($row['TO_ENTITY']);
				if($type === Sharing::TYPE_TO_USER && $row['LINK_STORAGE_ID'])
				{
					$subscribers[$row['LINK_STORAGE_ID']] = $id;
				}

				$ownerStorageIds[$row['REAL_STORAGE_ID']] = $row['REAL_STORAGE_ID'];
			}

			$currentHash = md5(implode('|', $ids));
			if (in_array($currentHash, $sliceHashes, true))
			{
				//it's cycle! recursion!

				break;
			}
			$sliceHashes[$currentDepth] = $currentHash;

			$currentDepth++;
		}

		$ownerStorageIds[$object->getStorageId()] = $object->getStorageId();

		foreach ($this->getSubscribresByStorages($ownerStorageIds) as $storageId => $userId)
		{
			$subscribers[$storageId] = $userId;
		}

		return $subscribers;
	}

	protected function getSubscribresByStorages(array $storagesId)
	{
		$subscribers = [];
		$storagesId = array_filter($storagesId);

		foreach ($storagesId as $k => $id)
		{
			if (Storage::isLoaded($id))
			{
				$probablyUserStorage = Storage::loadById($id);
				if ($probablyUserStorage && $probablyUserStorage->getProxyType() instanceof ProxyType\User)
				{
					$subscribers[$id] = $probablyUserStorage->getEntityId();
				}
				unset($storagesId[$k]);
			}
		}

		if (!$storagesId)
		{
			return $subscribers;
		}

		$storages = Storage::getList([
			'select' => ['ID', 'ENTITY_ID'],
			'filter' => [
				'=ENTITY_TYPE' => ProxyType\User::class,
				'@ID' => $storagesId,
			]
		]);


		foreach ($storages as $storage)
		{
			$subscribers[$storage['ID']] = $storage['ENTITY_ID'];
		}

		return $subscribers;
	}

	public function collectSubscribersSmart(BaseObject $object)
	{
		if (isset($this->subscribersByObjectId[$object->getId()]))
		{
			return $this->subscribersByObjectId[$object->getId()];
		}

		$this->subscribersByObjectId[$object->getId()] = $this->collectSubscribersGeneralWay($object, self::DIRECTION_PARENTS);

		return $this->subscribersByObjectId[$object->getId()];
	}

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
				[$type, $id] = Sharing::parseEntityValue($sharing->getToEntity());
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