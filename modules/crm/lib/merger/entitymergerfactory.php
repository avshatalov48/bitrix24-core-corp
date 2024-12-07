<?php

namespace Bitrix\Crm\Merger;

use Bitrix\Main;

class EntityMergerFactory
{
	final public static function isEntityTypeSupported(int $entityTypeId): bool
	{
		try
		{
			self::create($entityTypeId, 0);

			return true;
		}
		catch (Main\NotSupportedException)
		{
			return false;
		}
	}

	/** Create new entity merger by specified entity type ID.
	 * @static
	 * @param int $entityTypeID Entity type ID.
	 * @param int $currentUserID Current user ID.
	 * @param bool $enablePermissionCheck Permission check flag.
	 * @return EntityMerger
	 */
	public static function create($entityTypeID, $currentUserID, $enablePermissionCheck = false)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentException('Is not defined', 'entityTypeID');
		}

		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			return new LeadMerger($currentUserID, $enablePermissionCheck);
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			return new DealMerger($currentUserID, $enablePermissionCheck);
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			return new ContactMerger($currentUserID, $enablePermissionCheck);
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			return new CompanyMerger($currentUserID, $enablePermissionCheck);
		}
		elseif (
			$entityTypeID === \CCrmOwnerType::Quote
			|| $entityTypeID === \CCrmOwnerType::SmartInvoice
			|| \CCrmOwnerType::isPossibleDynamicTypeId($entityTypeID)
		)
		{
			return new FactoryBasedMerger(
				$entityTypeID,
				$currentUserID,
				$enablePermissionCheck,
			);
		}
		else
		{
			throw new Main\NotSupportedException("Entity type: '".\CCrmOwnerType::ResolveName($entityTypeID)."' is not supported in current context");
		}
	}
}
