<?php

namespace Bitrix\Crm\Service\Communication\Utils;

class Common
{
	public static function isClientEntityTypeId(int $entityTypeId): bool
	{
		return in_array($entityTypeId, self::getClientEntityTypeIds(), true);
	}

	public static function getClientEntityTypeIds(): array
	{
		return [\CCrmOwnerType::Contact, \CCrmOwnerType::Company];
	}
}
