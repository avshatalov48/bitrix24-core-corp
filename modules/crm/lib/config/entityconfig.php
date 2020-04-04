<?php
namespace Bitrix\Crm\Config;
use Bitrix\Main;
use Bitrix\Crm;

class EntityConfig
{
	/**
	 * Get entity settings
	 * @param int $entityTypeID Entity type ID.
	 * @param int $entityID Entity ID.
	 * @param int $userID User ID.
	 * @return mixed|null
	 * @throws Main\ArgumentException
	 */
	public static function get($entityTypeID, $entityID, $userID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}
		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentException('Is not defined or invalid', 'entityTypeID');
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}
		if($userID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'userID');
		}

		$dbResult = Entity\EntityConfigTable::getList(
			array(
				'filter' => array(
					'=ENTITY_TYPE_ID' => $entityTypeID,
					'=ENTITY_ID' => $entityID,
					'=USER_ID' => $userID
				),
				'select' => array('SETTINGS')
			)
		);

		$fields = $dbResult->fetch();
		return is_array($fields) && isset($fields['SETTINGS']) ? unserialize($fields['SETTINGS']) : null;
	}
	/**
	 * Register entity settings.
	 * @param int $entityTypeID Entity type ID.
	 * @param int $entityID Entity ID.
	 * @param int $userID User ID.
	 * @param array $settings Entity settings.
	 * @throws Main\ArgumentException
	 */
	public static function set($entityTypeID, $entityID, $userID, array $settings)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}
		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentException('Is not defined or invalid', 'entityTypeID');
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}
		if($userID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'userID');
		}

		Entity\EntityConfigTable::upsert(
			array(
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID,
				'USER_ID' => $userID,
				'SETTINGS' => serialize($settings)
			)
		);
	}
	/**
	 * Unregister entity settings.
	 * @param int $entityTypeID Entity type ID.
	 * @param int $entityID Entity ID.
	 * @param int $userID User ID.
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws \Exception
	 */
	public static function remove($entityTypeID, $entityID, $userID = 0)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}
		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentException('Is not defined or invalid', 'entityTypeID');
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}

		if($userID > 0)
		{
			Entity\EntityConfigTable::delete(array('ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $entityID, 'USER_ID' => $userID));
		}
		else
		{
			Entity\EntityConfigTable::deleteByEntity($entityTypeID, $entityID);
		}
	}
}