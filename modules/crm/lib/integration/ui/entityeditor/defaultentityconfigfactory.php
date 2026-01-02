<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor;

use Bitrix\Crm\Integration\UI\EntityEditor\DefaultEntityConfig\CompanyDefaultEntityConfig;
use Bitrix\Crm\Integration\UI\EntityEditor\DefaultEntityConfig\ContactDefaultEntityConfig;
use Bitrix\Crm\Integration\UI\EntityEditor\DefaultEntityConfig\DealDefaultEntityConfig;
use Bitrix\Crm\Integration\UI\EntityEditor\DefaultEntityConfig\DynamicDefaultEntityConfig;
use Bitrix\Crm\Integration\UI\EntityEditor\DefaultEntityConfig\QuoteDefaultEntityConfig;
use Bitrix\Crm\Integration\UI\EntityEditor\DefaultEntityConfig\LeadDefaultEntityConfig;
use Bitrix\Crm\Integration\UI\EntityEditor\DefaultEntityConfig\SmartDocumentDefaultEntityConfig;
use Bitrix\Crm\Integration\UI\EntityEditor\DefaultEntityConfig\SmartInvoiceDefaultEntityConfig;
use Bitrix\Crm\Service\Container;
use CCrmFieldMulti;
use CCrmOwnerType;

final class DefaultEntityConfigFactory
{
	public static function create(int $entityTypeId): ?AbstractDefaultEntityConfig
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if ($factory === null)
		{
			return null;
		}

		$userFieldNames = array_keys($factory->getUserFields());
		$multiFieldNames = array_keys(CCrmFieldMulti::GetEntityTypeInfos());

		$config = match ($entityTypeId) {
			CCrmOwnerType::Lead => new LeadDefaultEntityConfig($userFieldNames, $multiFieldNames),
			CCrmOwnerType::Deal => new DealDefaultEntityConfig($userFieldNames),
			CCrmOwnerType::Contact => new ContactDefaultEntityConfig($userFieldNames, $multiFieldNames),
			CCrmOwnerType::Company => new CompanyDefaultEntityConfig($userFieldNames, $multiFieldNames),
			CCrmOwnerType::Quote => new QuoteDefaultEntityConfig($userFieldNames),
			CCrmOwnerType::SmartInvoice => new SmartInvoiceDefaultEntityConfig($userFieldNames),
			default => null,
		};

		if ($config !== null)
		{
			return $config;
		}

		if (CCrmOwnerType::isDynamicTypeBasedStaticEntity($entityTypeId))
		{
			return new SmartDocumentDefaultEntityConfig($entityTypeId);
		}

		if (CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			return new DynamicDefaultEntityConfig($factory, $userFieldNames);
		}

		return null;
	}
}
