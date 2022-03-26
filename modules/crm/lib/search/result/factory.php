<?php

namespace Bitrix\Crm\Search\Result;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\NotImplementedException;

class Factory
{
	public static function createProvider(int $entityTypeId): Provider
	{
		switch ($entityTypeId)
		{
			case \CCrmOwnerType::Lead:
				return new \Bitrix\Crm\Search\Result\Provider\IndexSupported\LeadProvider();
			case \CCrmOwnerType::Deal:
				return new \Bitrix\Crm\Search\Result\Provider\IndexSupported\DealProvider();
			case \CCrmOwnerType::Contact:
				return new \Bitrix\Crm\Search\Result\Provider\IndexSupported\ContactProvider();
			case \CCrmOwnerType::Company:
				return new \Bitrix\Crm\Search\Result\Provider\IndexSupported\CompanyProvider();
			case \CCrmOwnerType::Invoice:
				return new \Bitrix\Crm\Search\Result\Provider\InvoiceProvider();
			default:
				$factory = Container::getInstance()->getFactory($entityTypeId);
				if ($factory)
				{
					return new \Bitrix\Crm\Search\Result\Provider\FactoryBased($factory);
				}

				throw new NotImplementedException(
					\CCrmOwnerType::ResolveName($entityTypeId) . ' search result provider is not implemented'
				);
		}
	}

	public static function createResultAdapter(int $entityTypeId): Adapter
	{
		switch ($entityTypeId)
		{
			case \CCrmOwnerType::Lead:
				return new \Bitrix\Crm\Search\Result\Adapter\LeadAdapter();
			case \CCrmOwnerType::Deal:
				return new \Bitrix\Crm\Search\Result\Adapter\DealAdapter();
			case \CCrmOwnerType::Contact:
				return new \Bitrix\Crm\Search\Result\Adapter\ContactAdapter();
			case \CCrmOwnerType::Company:
				return new \Bitrix\Crm\Search\Result\Adapter\CompanyAdapter();
			case \CCrmOwnerType::Invoice:
				return new \Bitrix\Crm\Search\Result\Adapter\InvoiceAdapter();
			case \CCrmOwnerType::Quote:
				return new \Bitrix\Crm\Search\Result\Adapter\QuoteAdapter();
			default:
				$factory = Container::getInstance()->getFactory($entityTypeId);
				if ($factory)
				{
					if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
					{
						return new \Bitrix\Crm\Search\Result\Adapter\DynamicAdapter($factory);
					}
					if ($entityTypeId === \CCrmOwnerType::SmartInvoice)
					{
						return new \Bitrix\Crm\Search\Result\Adapter\SmartInvoiceAdapter($factory);
					}
				}

				throw new NotImplementedException(
					\CCrmOwnerType::ResolveName($entityTypeId) . ' search result provider is not implemented'
				);
		}
	}

	public static function getSupportedEntityTypeIds()
	{
		$supportedTypes = [
			\CCrmOwnerType::Lead,
			\CCrmOwnerType::Deal,
			\CCrmOwnerType::Contact,
			\CCrmOwnerType::Company,
			\CCrmOwnerType::Invoice,
			\CCrmOwnerType::Quote,
			\CCrmOwnerType::SmartInvoice,
		];

		$dynamicTypesMap = Container::getInstance()->getDynamicTypesMap()->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		]);

		foreach ($dynamicTypesMap->getTypes() as $type)
		{
			$supportedTypes[] = $type->getEntityTypeId();
		}

		return $supportedTypes;
	}
}
