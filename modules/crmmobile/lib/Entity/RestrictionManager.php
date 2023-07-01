<?php

namespace Bitrix\CrmMobile\Entity;

use Bitrix\Crm\Restriction\Bitrix24AccessRestriction;

final class RestrictionManager
{
	/**
	 * Returns true if entity is restricted.
	 *
	 * @param int $entityTypeId
	 * @return bool
	 */
	public static function isEntityRestricted(int $entityTypeId): bool
	{
		return !self::getEntityRestrictions($entityTypeId)->hasPermission();
	}

	/**
	 * Returns restriction for mobile application.
	 *
	 * @param int $entityTypeId
	 * @return Bitrix24AccessRestriction
	 */
	public static function getEntityRestrictions(int $entityTypeId): Bitrix24AccessRestriction
	{
		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			return \Bitrix\Crm\Restriction\RestrictionManager::getLeadsRestriction();
		}

		if ($entityTypeId === \CCrmOwnerType::Quote)
		{
			return \Bitrix\Crm\Restriction\RestrictionManager::getQuotesRestriction();
		}

		if ($entityTypeId === \CCrmOwnerType::Invoice || $entityTypeId === \CCrmOwnerType::SmartInvoice)
		{
			return \Bitrix\Crm\Restriction\RestrictionManager::getInvoicesRestriction();
		}

		$dynamicTypesRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getDynamicTypesLimitRestriction();
		if ($dynamicTypesRestriction->isCreateItemRestricted($entityTypeId))
		{
			return new Bitrix24AccessRestriction($dynamicTypesRestriction::FEATURE_NAME, false);
		}

		return new Bitrix24AccessRestriction('', true);
	}
}
