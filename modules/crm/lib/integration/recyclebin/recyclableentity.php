<?php
namespace Bitrix\Crm\Integration\Recyclebin;

use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Main;
use Bitrix\Recyclebin;

abstract class RecyclableEntity implements Recyclebin\Internals\Contracts\Recyclebinable
{
	abstract public static function getEntityName();

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
	 */
	abstract public static function moveFromRecyclebin(Recyclebin\Internals\Entity $entity);

	/**
	 * Erase entity from Recycle Bin.
	 *
	 * @param Recyclebin\Internals\Entity $entity
	 * @param array $params
	 *
	 * @return Main\Result|void
	 */
	abstract public static function removeFromRecyclebin(Recyclebin\Internals\Entity $entity, array $params = []);

	/**
	 * Prepare entity view.
	 *
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
	 * @return array
	 */
	abstract public static function getNotifyMessages(): array;

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
				$data = unserialize($data, ['allowed_classes' => false]);
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

	public static function getAdditionalData(): array
	{
		return [
			static::getEntityName() => [
				'LIMIT_DATA' => [
					'RESTORE' => [
						'DISABLE' => !Bitrix24Manager::isFeatureEnabled('recyclebin'),
						'SLIDER_CODE' => 'limit_crm_recyclebin_restore',
					]
				]
			]
		];
	}
}
