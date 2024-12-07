<?php

namespace Bitrix\Intranet\User\Filter\Provider;

use Bitrix\Main\Filter\EntityDataProvider;
use Bitrix\Main\Filter\UserSettings;

class DateUserDataProvider extends EntityDataProvider
{
	private UserSettings $settings;

	public function __construct(UserSettings $settings)
	{
		$this->settings = $settings;
	}

	public function getSettings(): UserSettings
	{
		return $this->settings;
	}

	public function prepareFields()
	{
		return [];
	}

	public function prepareFieldData($fieldID)
	{
		return [];
	}

	public function prepareFilterValue(array $rawFilterValue): array
	{
		$filterValue = parent::prepareFilterValue($rawFilterValue);

		$dateFieldsList = [
			[
				'FILTER_FIELD_NAME' => 'DATE_REGISTER',
				'FIELD_NAME' => 'DATE_REGISTER',
				'VALUE_FROM' => ($filterValue['DATE_REGISTER_from'] ?? false),
				'VALUE_TO' => ($filterValue['DATE_REGISTER_to'] ?? false)
			],
			[
				'FILTER_FIELD_NAME' => 'LAST_ACTIVITY_DATE',
				'FIELD_NAME' => 'LAST_ACTIVITY_DATE',
				'VALUE_FROM' => ($filterValue['LAST_ACTIVITY_DATE_from'] ?? false),
				'VALUE_TO' => ($filterValue['LAST_ACTIVITY_DATE_to'] ?? false)
			],
			[
				'FILTER_FIELD_NAME' => 'BIRTHDAY',
				'FIELD_NAME' => 'PERSONAL_BIRTHDAY',
				'VALUE_FROM' => ($filterValue['BIRTHDAY_from'] ?? false),
				'VALUE_TO' => ($filterValue['BIRTHDAY_to'] ?? false)
			]
		];

		foreach ($dateFieldsList as $field)
		{
			if (
				!empty($field['VALUE_FROM'])
				|| !empty($field['VALUE_TO'])
			)
			{
				$this->addFilterDateTime($filterValue, [
					'FILTER_FIELD_NAME' => $field['FILTER_FIELD_NAME'],
					'FIELD_NAME' => $field['FIELD_NAME'],
					'VALUE_FROM' => ($field['VALUE_FROM'] ?? $filterValue[$field['FILTER_FIELD_NAME']]),
					'VALUE_TO' => ($field['VALUE_TO'] ?? $filterValue[$field['FILTER_FIELD_NAME']])
				]);
			}
		}

		return $filterValue;
	}

	private function addFilterDateTime(&$filter, array $params = []): void
	{
		$filterFieldName = ($params['FILTER_FIELD_NAME'] ?? '');
		$valueFrom = ($params['VALUE_FROM'] ?? '');
		$valueTo = ($params['VALUE_TO'] ?? '');

		if (
			$filterFieldName === ''
			|| (
				$valueFrom === ''
				&& $valueTo === ''
			)
		)
		{
			return;
		}

		$fieldName = (!empty($params['FIELD_NAME']) ? $params['FIELD_NAME'] : $filterFieldName);
		unset($filter['>=' . $filterFieldName], $filter['<=' . $filterFieldName]);

		if ($valueFrom <> '')
		{
			$filter['>=' . $fieldName] = $valueFrom;
		}

		if ($valueTo <> '')
		{
			$filter['<=' . $fieldName] = $valueTo;
		}
	}
}