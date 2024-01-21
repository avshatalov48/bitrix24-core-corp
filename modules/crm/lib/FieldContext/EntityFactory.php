<?php

namespace Bitrix\Crm\FieldContext;

use Bitrix\Crm\FieldContext\Model\CompanyTable;
use Bitrix\Crm\FieldContext\Model\ContactTable;
use Bitrix\Crm\FieldContext\Model\DealTable;
use Bitrix\Crm\FieldContext\Model\LeadTable;
use Bitrix\Crm\FieldContext\Model\QuoteTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Traits\Singleton;

class EntityFactory
{
	use Singleton;

	public function getEntity(int $entityTypeId): ?EntityFieldContextTable
	{
		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);

			$className = $factory?->getFieldsContextDataClass();
			if ($className)
			{
				return new $className;
			}

			return null;
		}

		return match ($entityTypeId)
		{
			\CCrmOwnerType::Lead => new LeadTable(),
			\CCrmOwnerType::Deal => new DealTable(),
			\CCrmOwnerType::Contact => new ContactTable(),
			\CCrmOwnerType::Company => new CompanyTable(),
			\CCrmOwnerType::Quote => new QuoteTable(),
			default => null,
		};
	}

	public function hasEntity(int $entityTypeId): bool
	{
		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);

			$className = $factory?->getFieldsContextDataClass();

			return (bool)$className;
		}

		return in_array($entityTypeId, [
			\CCrmOwnerType::Lead,
			\CCrmOwnerType::Deal,
			\CCrmOwnerType::Contact,
			\CCrmOwnerType::Company,
			\CCrmOwnerType::Quote,
		], true);
	}
}