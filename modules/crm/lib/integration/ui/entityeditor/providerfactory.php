<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor;

use CCrmOwnerType;

final class ProviderFactory
{
	public static function create(string $entityTypeName, int $entityId = 0, array $params = []): BaseProvider
	{
		switch ($entityTypeName)
		{
			case CCrmOwnerType::DealName:
				return new DealProvider($entityId, $params);

			case CCrmOwnerType::ContactName:
				return new ContactProvider($entityId, $params);

			case CCrmOwnerType::CompanyName:
				return new CompanyProvider($entityId, $params);

			default:
				throw new \DomainException("Entity provider {$entityTypeName} not found.");
		}
	}
}
