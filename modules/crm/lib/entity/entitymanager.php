<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Main;

class EntityManager
{
	public static function resolveByTypeID($entityTypeID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			return Lead::getInstance();
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			return Deal::getInstance();
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			return Contact::getInstance();
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			return Company::getInstance();
		}

		return null;
	}

	/**
	 * Select only existed entity IDs.
	 * @param int $entityTypeID Entity Type ID to check.
	 * @param array $entityIDs Entity IDs to check.
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function selectExisted($entityTypeID, array $entityIDs)
	{
		if(empty($entityIDs))
		{
			return array();
		}

		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			return Lead::selectExisted($entityIDs);
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			return Deal::selectExisted($entityIDs);
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			return Contact::selectExisted($entityIDs);
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			return Company::selectExisted($entityIDs);
		}

		return $entityIDs;
	}
}