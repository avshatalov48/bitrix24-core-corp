<?php

namespace Bitrix\Crm\Order\Matcher;

use Bitrix\Crm\Integrity\ActualEntitySelector;

class ContactMatcher extends BaseEntityMatcher
{
	protected function getEntityClassName()
	{
		return '\CCrmContact';
	}

	protected function getEntityMergerClassName()
	{
		return '\Bitrix\Crm\Merger\ContactMerger';
	}

	protected function getEntityTypeId()
	{
		return \CCrmOwnerType::Contact;
	}

	protected function getEntityTypeName()
	{
		return \CCrmOwnerType::ContactName;
	}

	protected function getDuplicateSearchParameters()
	{
		return [
			ActualEntitySelector::SEARCH_PARAM_PERSON,
			ActualEntitySelector::SEARCH_PARAM_EMAIL,
			ActualEntitySelector::SEARCH_PARAM_PHONE
		];
	}

	protected function getEntityFields()
	{
		$fields = parent::getEntityFields();

		if (isset($this->relation[\CCrmOwnerType::Company]))
		{
			$fields['COMPANY_ID'] = $this->relation[\CCrmOwnerType::Company];
		}

		return $fields;
	}

	protected function prepareGeneralField(&$fields, $property)
	{
		switch ($property['CRM_FIELD_CODE'])
		{
			case 'FULL_NAME':
				[$lastName, $firstName, $secondName] = explode(' ', $property['VALUE']);

				if (!isset($fields['LAST_NAME']) && !empty($lastName))
				{
					$fields['LAST_NAME'] = $lastName;
				}

				if (!isset($fields['NAME']) && !empty($firstName))
				{
					$fields['NAME'] = $firstName;
				}

				if (!isset($fields['SECOND_NAME']) && !empty($secondName))
				{
					$fields['SECOND_NAME'] = $secondName;
				}

				break;
		}

		parent::prepareGeneralField($fields, $property);
	}

	protected function getFieldsToCreate(array $fields)
	{
		$fields = parent::getFieldsToCreate($fields);

		if (!isset($fields['SOURCE_ID']))
		{
			$fields['SOURCE_ID'] = 'STORE';
		}

		return $fields;
	}
}