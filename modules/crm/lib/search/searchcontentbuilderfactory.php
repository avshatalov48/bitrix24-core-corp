<?php
namespace Bitrix\Crm\Search;
use Bitrix\Main;
class SearchContentBuilderFactory
{
	public static function create($entityTypeID)
	{
		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			return new LeadSearchContentBuilder();
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			return new ContactSearchContentBuilder();
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			return new CompanySearchContentBuilder();
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			return new DealSearchContentBuilder();
		}
		elseif($entityTypeID === \CCrmOwnerType::Quote)
		{
			return new QuoteSearchContentBuilder();
		}
		elseif($entityTypeID === \CCrmOwnerType::Invoice)
		{
			return new InvoiceSearchContentBuilder();
		}
		elseif($entityTypeID === \CCrmOwnerType::Activity)
		{
			return new ActivitySearchContentBuilder();
		}
		elseif($entityTypeID === \CCrmOwnerType::Order)
		{
			return new OrderSearchContentBuilder();
		}
		elseif (\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeID))
		{
			return new DynamicTypeSearchContentBuilder($entityTypeID);
		}
		else
		{
			throw new Main\NotSupportedException("Type: '".\CCrmOwnerType::resolveName($entityTypeID)."' is not supported in current context");
		}
	}
}