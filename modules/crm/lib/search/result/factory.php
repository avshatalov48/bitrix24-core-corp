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

	public static function createResultAdapter(int $entityTypeId, ?int $categoryId = null): Adapter
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);

		switch ($entityTypeId)
		{
			case \CCrmOwnerType::Lead:
				$adapter = new \Bitrix\Crm\Search\Result\Adapter\LeadAdapter();
				break;
			case \CCrmOwnerType::Deal:
				$adapter = new \Bitrix\Crm\Search\Result\Adapter\DealAdapter();
				break;
			case \CCrmOwnerType::Contact:
				$adapter = new \Bitrix\Crm\Search\Result\Adapter\ContactAdapter();
				break;
			case \CCrmOwnerType::Company:
				$adapter = new \Bitrix\Crm\Search\Result\Adapter\CompanyAdapter();
				break;
			case \CCrmOwnerType::Invoice:
				$adapter = new \Bitrix\Crm\Search\Result\Adapter\InvoiceAdapter();
				break;
			case \CCrmOwnerType::Quote:
				$adapter = new \Bitrix\Crm\Search\Result\Adapter\QuoteAdapter();
				break;
			default:
				if ($factory)
				{
					if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
					{
						$adapter = new \Bitrix\Crm\Search\Result\Adapter\DynamicAdapter($factory);
					}
					if ($entityTypeId === \CCrmOwnerType::SmartInvoice)
					{
						$adapter = new \Bitrix\Crm\Search\Result\Adapter\SmartInvoiceAdapter($factory);
					}
				}

				break;
		}

		if (!$adapter)
		{
			throw new NotImplementedException(
				\CCrmOwnerType::ResolveName($entityTypeId) . ' search result provider is not implemented'
			);
		}

		if ($factory && $categoryId)
		{
			$adapter->setCategory(
				$factory->getCategory($categoryId)
			);
		}

		return $adapter;
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
