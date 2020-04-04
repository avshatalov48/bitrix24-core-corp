<?php
namespace Bitrix\Crm\Integration\Recyclebin;

use Bitrix\Main;
use Bitrix\Recyclebin;

class RecyclableEntity implements Recyclebin\Internals\Contracts\Recyclebinable
{
	public static function getEntityName()
	{
		throw new Main\NotImplementedException('Method '.__METHOD__.' must be implemented by successor');
	}

	public static function createRecycleBinEntity($entityID)
	{
		if(!Main\Loader::includeModule('recyclebin'))
		{
			throw new Main\InvalidOperationException("Could not load module RecycleBin.");
		}

		return new Recyclebin\Internals\Entity($entityID, static::getEntityName(),'crm');
	}

	/**
	 * Recover entity from Recycle Bin.
	 * @param Recyclebin\Internals\Entity $entity
	 * @return bool|void
	 * @throws Main\NotImplementedException
	 */
	public static function moveFromRecyclebin(Recyclebin\Internals\Entity $entity)
	{
		throw new Main\NotImplementedException('Method '.__METHOD__.' must be implemented by successor');
	}

	/**
	 * Erase entity from Recycle Bin.
	 * @param Recyclebin\Internals\Entity $entity
	 * @return Main\Result|void
	 * @throws Main\NotImplementedException
	 */
	public static function removeFromRecyclebin(Recyclebin\Internals\Entity $entity)
	{
		throw new Main\NotImplementedException('Method '.__METHOD__.' must be implemented by successor');
	}

	/**
	 * Prepare entity view.
	 * @param Recyclebin\Internals\Entity $entity
	 * @return boolean|void
	 * @throws Main\NotImplementedException
	 */
	public static function previewFromRecyclebin(Recyclebin\Internals\Entity $entity)
	{
		throw new Main\NotImplementedException();
	}

	/**
	 * Get message array for Recycle Bin action's notification
	 * @throws Main\NotImplementedException
	 * @return array|void
	 */
	public static function getNotifyMessages()
	{
		throw new Main\NotImplementedException('Method '.__METHOD__.' must be implemented by successor');
	}

	protected static function prepareDataSlots(Recyclebin\Internals\Entity $entity)
	{
		$slots = array();
		foreach($entity->getData() as $item)
		{
			$key = isset($item['ACTION']) ? $item['ACTION'] : '';
			if($key === '')
			{
				continue;
			}

			$data = isset($item['DATA']) ? $item['DATA'] : '';
			if(is_string($data))
			{
				$data = unserialize($data);
			}

			if(is_array($data))
			{
				$slots[$key] = $data;
			}
		}
		return $slots;
	}
	protected static function prepareDataSlotMap(Recyclebin\Internals\Entity $entity)
	{
		$slotMap = array();
		foreach($entity->getData() as $item)
		{
			$key = isset($item['ACTION']) ? $item['ACTION'] : '';
			$itemID = isset($item['ID']) ? (int)$item['ID'] : 0;

			if($key !== '' && $itemID > 0)
			{
				$slotMap[$key] = $itemID;
			}
		}
		return $slotMap;
	}
}