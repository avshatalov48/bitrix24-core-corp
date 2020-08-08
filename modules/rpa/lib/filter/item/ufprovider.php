<?php

namespace Bitrix\Rpa\Filter\Item;

use Bitrix\Crm\UI\Filter\EntityHandler;
use Bitrix\Main\Filter\EntityUFDataProvider;
use Bitrix\Main\Loader;

class UfProvider extends EntityUFDataProvider
{
	public function getGridColumns(): array
	{
		$result = [];

		$userFields = $this->getUserFields();

		foreach($userFields as $userField)
		{
			$result[] = $this->getGridColumn($userField);
		}

		return $result;
	}

	protected function getGridColumn(array $userField)
	{
		$typeID = $userField['USER_TYPE']['USER_TYPE_ID'];
		$fieldName = $userField['FIELD_NAME'];

		$result = [
			'id' => $fieldName,
			'name' => $this->getFieldLabel($userField),
			'default' => ($userField['SHOW_FILTER'] !== 'N'),
		];

		if($typeID === 'employee')
		{

		}
		elseif($typeID === 'string' || $typeID === 'url' || $typeID === 'address' || $typeID === 'money')
		{

		}
		elseif($typeID === 'integer' || $typeID === 'double')
		{
			$result['type'] = 'number';
		}
		elseif($typeID === 'boolean')
		{
			$result['type'] = 'checkbox';
		}
		elseif($typeID === 'datetime' || $typeID === 'date')
		{
			$result['type'] = 'date';
		}
		elseif($typeID === 'enumeration'
			|| $typeID === 'iblock_element'
			|| $typeID === 'iblock_section'
			|| $typeID === 'crm_status'
		)
		{

		}
		elseif($typeID === 'crm')
		{

		}
		else
		{

		}

		return $result;
	}

	protected function getFieldLabel(array $userField): string
	{
		$fieldLabel = '';
		if(isset($userField['LIST_FILTER_LABEL']))
		{
			$fieldLabel = $userField['LIST_FILTER_LABEL'];
		}
		if(empty($fieldLabel) && isset($userField['LIST_COLUMN_LABEL']))
		{
			$fieldLabel = $userField['LIST_COLUMN_LABEL'];
		}
		if(empty($fieldLabel) && isset($userField['EDIT_FORM_LABEL']))
		{
			$fieldLabel = $userField['EDIT_FORM_LABEL'];
		}
		if(empty($fieldLabel))
		{
			return $userField['FIELD_NAME'];
		}

		return $fieldLabel;
	}

	public function prepareListFilter(array &$filter, array $filterFields, array $requestFilter)
	{
		$userFields = $this->getUserFields();
		foreach($filterFields as $filterField)
		{
			$id = $filterField['id'];
			$isProcessed = false;
			if(isset($filterField['type']))
			{
				if($filterField['type'] === 'number' || $filterField['type'] === 'date' || $filterField['type'] === 'datetime')
				{
					if(!empty($requestFilter[$id.'_from']))
					{
						$filter['>='.$id] = $requestFilter[$id.'_from'];
					}
					if(!empty($requestFilter[$id.'_to']))
					{
						$filter['<='.$id] = $requestFilter[$id.'_to'];
					}
					$isProcessed = true;
				}
			}
			if(!$isProcessed && !empty($requestFilter[$id]) && isset($userFields[$id]))
			{
				$filter[$id] = $requestFilter[$id];
				if($userFields[$id]['USER_TYPE_ID'] === 'crm' && Loader::includeModule('crm'))
				{
					EntityHandler::internalize($filterFields, $filter);
				}
			}
		}
	}
}