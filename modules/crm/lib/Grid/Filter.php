<?php

namespace Bitrix\Crm\Grid;

use Bitrix\Crm;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\UI\Filter\FieldAdapter;

class Filter
{
	public function __construct(private Crm\Filter\Filter $filter)
	{

	}

	public function getFields(array $fieldIds): Result
	{
		$result = new Result();

		$fields = [];
		foreach ($fieldIds as $fieldId)
		{
			$field = $this->filter->getField($fieldId);
			if ($field)
			{
				$fields[] = $field;
			}
			else
			{
				return $result->addError(
					new Error(Loc::getMessage('CRM_GRID_FILTER_FIELD_NOT_FOUND', ['FIELD_ID' => $fieldId]))
				);
			}
		}

		$adaptedFields = [];
		foreach ($fields as $field)
		{
			$adaptedFields[] = FieldAdapter::adapt($field->toArray());
		}

		return $result->setData($adaptedFields);
	}
}
