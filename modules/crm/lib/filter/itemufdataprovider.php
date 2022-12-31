<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\UI\Filter\EntityHandler;

class ItemUfDataProvider extends UserFieldDataProvider
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
}
