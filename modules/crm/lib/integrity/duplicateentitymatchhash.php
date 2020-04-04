<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;
class DuplicateEntityMatchHash
{
	public static function register($entityTypeID, $entityID, $typeID, $matchHash, $isPrimary = true, $scope = '')
	{
		Entity\DuplicateEntityMatchHashTable::upsert(
			array(
				'ENTITY_ID' => $entityID,
				'ENTITY_TYPE_ID' => $entityTypeID,
				'TYPE_ID' => $typeID,
				'MATCH_HASH' => $matchHash,
				'SCOPE' => $scope,
				'IS_PRIMARY' => $isPrimary,
			)
		);

	}
	public static function unregister($entityTypeID, $entityID, $typeID, $matchHash, $scope = '')
	{
		Entity\DuplicateEntityMatchHashTable::delete(
			array(
				'ENTITY_ID'=> $entityID,
				'ENTITY_TYPE_ID' => $entityTypeID,
				'TYPE_ID' => $typeID,
				'MATCH_HASH' => $matchHash
			)
		);
	}
	public static function unregisterEntity($entityTypeID, $entityID, $typeID = 0, $scope = null)
	{
		Entity\DuplicateEntityMatchHashTable::deleteByFilter(
			array('ENTITY_ID' => $entityID, 'ENTITY_TYPE_ID' => $entityTypeID, 'TYPE_ID' => $typeID, 'SCOPE' => $scope)
		);
	}
}