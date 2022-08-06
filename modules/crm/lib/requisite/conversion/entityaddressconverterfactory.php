<?php

namespace Bitrix\Crm\Requisite\Conversion;

use Bitrix\Main;

class EntityAddressConverterFactory
{
	public static function create($entityTypeID)
	{
		if ($entityTypeID === \CCrmOwnerType::Contact)
		{
			return new ContactAddressConverter(0, false);
		}
		else if ($entityTypeID === \CCrmOwnerType::Company)
		{
			return new CompanyAddressConverter(0, false);
		}
		else
		{
			throw new Main\NotSupportedException(
				"Type: '".\CCrmOwnerType::resolveName($entityTypeID)."' is not supported in current context"
			);
		}
	}
}