<?php

namespace Bitrix\Crm\Order\Matcher;

use Bitrix\Crm\Integrity\ActualEntitySelector;

class CompanyMatcher extends BaseEntityMatcher
{
	protected function getEntityClassName()
	{
		return '\CCrmCompany';
	}

	protected function getEntityMergerClassName()
	{
		return '\Bitrix\Crm\Merger\CompanyMerger';
	}

	protected function getEntityTypeId()
	{
		return \CCrmOwnerType::Company;
	}

	protected function getEntityTypeName()
	{
		return \CCrmOwnerType::CompanyName;
	}

	protected function getDuplicateSearchParameters()
	{
		return [
			ActualEntitySelector::SEARCH_PARAM_ORGANIZATION,
			ActualEntitySelector::SEARCH_PARAM_EMAIL,
			ActualEntitySelector::SEARCH_PARAM_PHONE
		];
	}

	protected function prepareGeneralField(&$fields, $property)
	{
		// ToDo unify all fields
		parent::prepareGeneralField($fields, $property);
	}
}