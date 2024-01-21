<?php

namespace Bitrix\Crm\Search;

use Bitrix\Main\NotSupportedException;
use CCrmOwnerType;

class SearchContentBuilderFactory
{
	public static function create(int $entityTypeId): SearchContentBuilder
	{
		if ($entityTypeId === CCrmOwnerType::Lead)
		{
			$builder = new LeadSearchContentBuilder();
		}
		elseif ($entityTypeId === CCrmOwnerType::Contact)
		{
			$builder = new ContactSearchContentBuilder();
		}
		elseif ($entityTypeId === CCrmOwnerType::Company)
		{
			$builder = new CompanySearchContentBuilder();
		}
		elseif ($entityTypeId === CCrmOwnerType::Deal)
		{
			$builder = new DealSearchContentBuilder();
		}
		elseif ($entityTypeId === CCrmOwnerType::Quote)
		{
			$builder = new QuoteSearchContentBuilder();
		}
		elseif ($entityTypeId === CCrmOwnerType::Invoice)
		{
			$builder = new InvoiceSearchContentBuilder();
		}
		elseif ($entityTypeId === CCrmOwnerType::Activity)
		{
			$builder = new ActivitySearchContentBuilder();
		}
		elseif ($entityTypeId === CCrmOwnerType::Order)
		{
			$builder = new OrderSearchContentBuilder();
		}
		elseif (CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
		{
			$builder = new DynamicTypeSearchContentBuilder($entityTypeId);
		}
		else
		{
			throw new NotSupportedException(
				"Type: '" . CCrmOwnerType::resolveName($entityTypeId) . "' is not supported in current context"
			);
		}

		return $builder;
	}
}
