<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\UI\Filter\EntityHandler;
use Bitrix\Main\Filter\EntityUFDataProvider;

class ItemUfDataProvider extends EntityUFDataProvider
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

	protected function getGridColumn(array $userField): array
	{
		$fieldName = $userField['FIELD_NAME'];

		$result = [
			'id' => $fieldName,
			'name' => $this->getFieldName($userField),
			'default' => ($userField['SHOW_FILTER'] !== 'N'),
		];

		return $result;
	}

	protected function getFieldName(array $userField): string
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
			if (isset($userFields[$id]))
			{
				$isProcessed = false;
				if (isset($filterField['type']))
				{
					if ($filterField['type'] === 'number' || $filterField['type'] === 'date' || $filterField['type'] === 'datetime')
					{
						if (!empty($requestFilter[$id.'_from']))
						{
							$filter['>='.$id] = $requestFilter[$id.'_from'];
						}
						if (!empty($requestFilter[$id.'_to']))
						{
							$filter['<='.$id] = $requestFilter[$id.'_to'];
						}
						$isProcessed = true;
					}
				}
				if (!$isProcessed && isset($requestFilter[$id]))
				{
					$filter[$id] = $requestFilter[$id];
					if ($userFields[$id]['USER_TYPE_ID'] === 'crm')
					{
						EntityHandler::internalize($filterFields, $filter);
					}
				}
			}
		}
	}
}