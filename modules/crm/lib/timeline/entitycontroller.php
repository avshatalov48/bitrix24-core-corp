<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Main;

class EntityController extends Controller
{
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Undefined;
	}
	public function onCreate($entityID, array $params)
	{
	}
	public function onModify($entityID, array $params)
	{
	}
	public function onDelete($entityID, array $params)
	{
	}
	public function onRestore($entityID, array $params)
	{
	}
	public function getSupportedPullCommands()
	{
		return array();
	}
	public function prepareSearchContent(array $params)
	{
		return '';
	}

	public function prepareEntityPushTag($entityID)
	{
		return TimelineEntry::prepareEntityPushTag($this->getEntityTypeID(), $entityID);
	}

	/**
	 * Register existed entity in retrospect mode.
	 * @param int $ownerID Entity ID
	 * @return void
	 */
	public function register($ownerID, array $options = null)
	{
	}
	public static function prepareMultiFieldInfo(array &$item)
	{
		$items = array($item);
		self::prepareMultiFieldInfoBulk($items);
		$item = $items[0];
	}
	public static function prepareMultiFieldInfoBulk(array &$items)
	{
		$map = array();
		foreach($items as $ID => $item)
		{
			if(!isset($item['ASSOCIATED_ENTITY']) || !isset($item['ASSOCIATED_ENTITY']['COMMUNICATION']))
			{
				continue;
			}

			$communication = $item['ASSOCIATED_ENTITY']['COMMUNICATION'];
			$typeName = $communication['TYPE'] ? $communication['TYPE'] : '';
			$entityID = $communication['ENTITY_ID'] ? $communication['ENTITY_ID'] : 0;
			$entityTypeID = $communication['ENTITY_TYPE_ID'] ? $communication['ENTITY_TYPE_ID'] : \CCrmOwnerType::Undefined;

			if($typeName === '' || $entityID <= 0 || !\CCrmOwnerType::IsDefined($entityTypeID))
			{
				continue;
			}

			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			if(!isset($map[$typeName]))
			{
				$map[$typeName] = array();
			}

			if(!isset($map[$typeName][$entityTypeName]))
			{
				$map[$typeName][$entityTypeName] = array();
			}

			if(!isset($map[$typeName][$entityTypeName][$entityID]))
			{
				$map[$typeName][$entityTypeName][$entityID] = array();
			}

			$map[$typeName][$entityTypeName][$entityID][] = $ID;
		}

		$multifields = array();
		foreach($map as $typeName => $item)
		{
			$entityTypeNames = array_keys($item);
			foreach($entityTypeNames as $entityTypeName)
			{
				if(!isset($multifields[$typeName]))
				{
					$multifields[$typeName] = array();
				}

				if(!isset($multifields[$typeName][$entityTypeName]))
				{
					$multifields[$typeName][$entityTypeName] = array();
				}

				$multifields[$typeName][$entityTypeName] = \CCrmFieldMulti::PrepareEntityDataBulk(
					$typeName,
					$entityTypeName,
					array_keys($item[$entityTypeName]),
					array('ENABLE_COMPLEX_NAME' => true, 'LIMIT' => 5)
				);
			}
		}

		foreach($multifields as $typeName => $item)
		{
			if(!isset($map[$typeName]))
			{
				continue;
			}

			$entityTypeNames = array_keys($item);
			foreach($entityTypeNames as $entityTypeName)
			{
				if(!isset($map[$typeName][$entityTypeName]))
				{
					continue;
				}

				$entityTypeData = $item[$entityTypeName];
				$entityIDs = array_keys($entityTypeData);
				foreach($entityIDs as $entityID)
				{
					if(!isset($map[$typeName][$entityTypeName][$entityID]))
					{
						continue;
					}

					$entityData = $entityTypeData[$entityID];
					foreach($map[$typeName][$entityTypeName][$entityID] as $ID)
					{
						$items[$ID][$typeName] = $entityData;
					}
				}
			}
		}
	}
}