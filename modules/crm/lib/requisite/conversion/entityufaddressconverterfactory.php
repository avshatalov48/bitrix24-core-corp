<?php

namespace Bitrix\Crm\Requisite\Conversion;

use Bitrix\Main;

class EntityUfAddressConverterFactory
{
	public static function create(int $entityTypeID, int $sourceEntityTypeId, string $sourceUserFieldName)
	{
		if ($entityTypeID === \CCrmOwnerType::Contact)
		{
			return new ContactUfAddressConverter($sourceEntityTypeId, $sourceUserFieldName, 0, false);
		}
		else if ($entityTypeID === \CCrmOwnerType::Company)
		{
			return new CompanyUfAddressConverter($sourceEntityTypeId, $sourceUserFieldName, 0, false);
		}
		else
		{
			throw new Main\NotSupportedException(
				"Type: '".\CCrmOwnerType::resolveName($entityTypeID)."' is not supported in current context"
			);
		}
	}
}