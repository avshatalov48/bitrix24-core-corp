<?php

namespace Bitrix\Crm\ItemMiniCard\Factory;

use Bitrix\Crm\ItemMiniCard\Contract\Provider;
use Bitrix\Crm\ItemMiniCard\Provider\EntityProvider\CompanyProvider;
use Bitrix\Crm\ItemMiniCard\Provider\EntityProvider\ContactProvider;
use Bitrix\Crm\ItemMiniCard\Provider\EntityProvider\DealProvider;
use Bitrix\Crm\ItemMiniCard\Provider\EntityProvider\DynamicProvider;
use Bitrix\Crm\ItemMiniCard\Provider\EntityProvider\LeadProvider;
use Bitrix\Crm\ItemMiniCard\Provider\EntityProvider\QuoteProvider;
use Bitrix\Crm\ItemMiniCard\Provider\EntityProvider\SmartInvoiceProvider;
use Bitrix\Crm\ItemMiniCard\Provider\OrderProvider;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use CCrmOwnerType;

final class ProviderFactory
{
	public function create(int $entityTypeId, int $entityId): ?Provider
	{
		if ($entityTypeId === CCrmOwnerType::Order)
		{
			if (!Loader::includeModule('sale'))
			{
				return null;
			}

			$order = Order::load($entityId);
			if ($order === null)
			{
				return null;
			}

			return new OrderProvider($order);
		}

		$item = Container::getInstance()->getFactory($entityTypeId)?->getItem($entityId);
		if ($item === null)
		{
			return null;
		}

		$provider = match ($item->getEntityTypeId()) {
			CCrmOwnerType::Lead => new LeadProvider($item),
			CCrmOwnerType::Deal => new DealProvider($item),
			CCrmOwnerType::Contact => new ContactProvider($item),
			CCrmOwnerType::Company => new CompanyProvider($item),
			CCrmOwnerType::Quote => new QuoteProvider($item),
			CCrmOwnerType::SmartInvoice => new SmartInvoiceProvider($item),
			default => null,
		};

		if ($provider !== null)
		{
			return $provider;
		}

		if (CCrmOwnerType::isDynamicTypeBasedStaticEntity($entityTypeId))
		{
			return null;
		}

		if (CCrmOwnerType::isPossibleDynamicTypeId($item->getEntityTypeId()))
		{
			return new DynamicProvider($item);
		}

		return null;
	}

	public function isAvailable(int $entityTypeId): bool
	{
		$entityTypeIds = [
			CCrmOwnerType::Lead,
			CCrmOwnerType::Deal,
			CCrmOwnerType::Contact,
			CCrmOwnerType::Company,
			CCrmOwnerType::Quote,
			CCrmOwnerType::SmartInvoice,
		];

		return in_array($entityTypeId, $entityTypeIds, true)
			|| ($entityTypeId === CCrmOwnerType::Order && Loader::includeModule('sale'))
			|| CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
		;
	}
}
