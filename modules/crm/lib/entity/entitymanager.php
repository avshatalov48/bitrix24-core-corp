<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Crm;
use Bitrix\Crm\Component\EntityDetails\BaseComponent;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Result;

class EntityManager
{
	public static function resolveByTypeID($entityTypeID)
	{
		if (!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}
		if ($entityTypeID === \CCrmOwnerType::Lead)
		{
			return Lead::getInstance();
		}
		if ($entityTypeID === \CCrmOwnerType::Deal)
		{
			return Deal::getInstance();
		}
		if ($entityTypeID === \CCrmOwnerType::Contact)
		{
			return Contact::getInstance();
		}
		if($entityTypeID === \CCrmOwnerType::Company)
		{
			return Company::getInstance();
		}
		if($entityTypeID === \CCrmOwnerType::Quote)
		{
			return Quote::getInstance();
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
			return [];
		}
		if ($entityTypeID === \CCrmOwnerType::Lead)
		{
			return Lead::selectExisted($entityIDs);
		}
		if ($entityTypeID === \CCrmOwnerType::Deal)
		{
			return Deal::selectExisted($entityIDs);
		}
		if ($entityTypeID === \CCrmOwnerType::Contact)
		{
			return Contact::selectExisted($entityIDs);
		}
		if ($entityTypeID === \CCrmOwnerType::Company)
		{
			return Company::selectExisted($entityIDs);
		}

		return $entityIDs;
	}
}
