<?php

namespace Bitrix\Crm\Search\Result;

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
			case \CCrmOwnerType::Quote:
				return new \Bitrix\Crm\Search\Result\Provider\QuoteProvider();
			default:
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
				throw new NotImplementedException(
					\CCrmOwnerType::ResolveName($entityTypeId) . ' search result provider is not implemented'
				);
		}
	}

	public static function getSupportedEntityTypeIds()
	{
		return [
			\CCrmOwnerType::Lead,
			\CCrmOwnerType::Deal,
			\CCrmOwnerType::Contact,
			\CCrmOwnerType::Company,
			\CCrmOwnerType::Invoice,
			\CCrmOwnerType::Quote,
		];
	}
}
