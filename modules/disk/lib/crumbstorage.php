<?php

namespace Bitrix\Disk;


use Bitrix\Main\Entity\Event;
use Bitrix\Main\EventManager;

class CrumbStorage
{
	/** @var  Driver */
	private static $instance;
	/** @var array  */
	private $crumbsByObjectId = array();

	protected function __construct()
	{
		$this->setEvents();
	}

	protected function setEvents()
	{
		$eventManager = EventManager::getInstance();

		$eventManager->addEventHandler(Driver::INTERNAL_MODULE_ID, 'FileOnAfterMove', array($this, 'onObjectOnAfterMove'));
		$eventManager->addEventHandler(Driver::INTERNAL_MODULE_ID, 'FolderOnAfterMove', array($this, 'onObjectOnAfterMove'));
		$eventManager->addEventHandler(Driver::INTERNAL_MODULE_ID, 'ObjectOnAfterMove', array($this, 'onObjectOnAfterMove'));
		$eventManager->addEventHandler(Driver::INTERNAL_MODULE_ID, 'onAfterRenameObject', array($this, 'onAfterRenameObject'));
	}

	private function __clone()
	{}

	/**
	 * Returns Singleton of CrumbStorage.
	 * @return CrumbStorage
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance))
		{
			self::$instance = new static;
		}

		return self::$instance;
	}

	/**
	 * Event listener, which cleans crumbs after move.
	 * @param Event $event Event.
	 * @return void
	 */
	public function onObjectOnAfterMove(Event $event)
	{
		$primaryData = $event->getParameter('id');
		if($primaryData)
		{
			$this->cleanByObjectId($primaryData['ID']);
		}
	}

	public function onAfterRenameObject(\Bitrix\Main\Event $event)
	{
		list($object) = $event->getParameters();
		if($object instanceof BaseObject)
		{
			$this->cleanByObjectId($object->getId());
		}
	}

	/**
	 * Cleans calculated crumbs by object id.
	 * @param int $objectId
	 * @return void
	 */
	public function cleanByObjectId($objectId)
	{
		unset($this->crumbsByObjectId[$objectId]);
	}

	/**
	 * Get list of crumbs by object.
	 * @param BaseObject $object BaseObject.
	 * @param bool   $includeSelf Append name of object.
	 * @return array
	 */
	public function getByObject(BaseObject $object, $includeSelf = false)
	{
		if(!isset($this->crumbsByObjectId[$object->getId()]))
		{
			$this->calculateCrumb($object);
		}
		if($includeSelf)
		{
			return $this->crumbsByObjectId[$object->getId()];
		}

		return array_slice($this->crumbsByObjectId[$object->getId()], 0, -1, true);
	}

	/**
	 * Calculates distance between folder and object.
	 * It may be useful when you want to build path under symbolic link.
	 * Notice: this method works correctly when calculates distance in the same storage.
	 *
	 * @param Folder $fromFolder From folder.
	 * @param BaseObject $toObject Destination object.
	 *
	 * @return array|null
	 */
	public function calculateDistance(Folder $fromFolder, BaseObject $toObject)
	{
		$fromFolder = $fromFolder->getRealObject();
		if (!$fromFolder)
		{
			return null;
		}

		$crumbs = $this->getByObject($toObject);
		$between = array();
		$found = false;
		if (
			$fromFolder->getStorageId() == $toObject->getStorageId() &&
			$fromFolder->getStorage()->getRootObjectId() == $fromFolder->getId()
		)
		{
			$found = true;
		}

		foreach (array_reverse($crumbs, true) as $objectId => $name)
		{
			if ($objectId == $fromFolder->getRealObjectId())
			{
				$found = true;
				break;
			}

			$between[$objectId] = $name;
		}

		$between = array_reverse($between, true);

		return $found? $between : null;
	}

	protected function calculateCrumb(BaseObject $object)
	{
		$parentId = $object->getParentId();
		if(!$parentId)
		{
			$this->crumbsByObjectId[$object->getId()] = array($this->fetchNameByObject($object));
			return $this->crumbsByObjectId[$object->getId()];
		}

		if(isset($this->crumbsByObjectId[$parentId]))
		{
			$this->crumbsByObjectId[$object->getId()] = $this->crumbsByObjectId[$parentId];
			$this->crumbsByObjectId[$object->getId()][$object->getId()] = $this->fetchNameByObject($object);

			return $this->crumbsByObjectId[$object->getId()];
		}

		$storage = $object->getStorage();
		$fake = Driver::getInstance()->getFakeSecurityContext();

		$this->crumbsByObjectId[$object->getId()] = array();
		foreach($object->getParents($fake, array('select' => array('ID', 'NAME', 'TYPE')), SORT_DESC) as $parent)
		{
			if($parent->getId() == $storage->getRootObjectId())
			{
				continue;
			}
			$this->crumbsByObjectId[$object->getId()][$parent->getId()] = $parent->getName();
		}
		unset($parent);

		$this->crumbsByObjectId[$parentId] = $this->crumbsByObjectId[$object->getId()];
		$this->crumbsByObjectId[$object->getId()][$object->getId()] = $this->fetchNameByObject($object);

		return $this->crumbsByObjectId[$object->getId()];
	}

	protected function fetchNameByObject(BaseObject $object)
	{
		return $object->getOriginalName();
	}
}