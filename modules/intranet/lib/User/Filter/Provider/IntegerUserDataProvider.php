<?php

namespace Bitrix\Intranet\User\Filter\Provider;

use Bitrix\Main\Filter\EntityDataProvider;
use Bitrix\Main\Filter\UserSettings;

class IntegerUserDataProvider extends EntityDataProvider
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

	private function getIntegerFieldList($gridFilter): array
	{
		$gridFilter['DEPARTMENT'] ??= null;

		return [
			[
				'FILTER_FIELD_NAME' => 'ID',
				'FIELD_NAME' => 'ID',
				'OPERATION' => '=',
				'VALUE' => $gridFilter['ID'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'PERSONAL_COUNTRY',
				'FIELD_NAME' => 'PERSONAL_COUNTRY',
				'OPERATION' => '@',
				'VALUE' => $gridFilter['PERSONAL_COUNTRY'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'WORK_COUNTRY',
				'FIELD_NAME' => 'WORK_COUNTRY',
				'OPERATION' => '@',
				'VALUE' => $gridFilter['WORK_COUNTRY'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'DEPARTMENT',
				'FIELD_NAME' => 'UF_DEPARTMENT',
				'OPERATION' => '=',
				'VALUE' => (
					!empty($gridFilter['DEPARTMENT'])
						? (
							preg_match('/^(?:DR|)(\d+)$/', $gridFilter['DEPARTMENT'], $matches)
							? $matches[1]
							: false
					)
						: false
				)
			],
			[
				'FILTER_FIELD_NAME' => 'DEPARTMENT',
				'FIELD_NAME' => 'UF_DEPARTMENT_FLAT',
				'OPERATION' => '=',
				'VALUE' => (
					$gridFilter['DEPARTMENT'] && preg_match('/^D(\d+)$/', $gridFilter['DEPARTMENT'], $matches)
						? $matches[1]
						: false
				)
			]
		];
	}

	private function addFilterInteger(&$filter, array $params = [])
	{
		$filterFieldName = ($params['FILTER_FIELD_NAME'] ?? '');
		$value = ($params['VALUE'] ?? '');

		if (
			$filterFieldName == ''
			|| (int)$value <= 0
		)
		{
			return;
		}

		$fieldName = (isset($params['FIELD_NAME']) && $params['FIELD_NAME'] <> '' ? $params['FIELD_NAME'] : $filterFieldName);
		$operation = ($params['OPERATION'] ?? '=');

		unset($filter[$fieldName]);
		$filter[$operation.$fieldName] = $value;
	}

	public function prepareFilterValue(array $rawFilterValue): array
	{
		foreach ($this->getIntegerFieldList($rawFilterValue) as $field)
		{
			$value = false;

			if (
				is_array($field['VALUE'])
				&& !empty($field['VALUE'])
			)
			{
				$value = $field['VALUE'];
			}
			elseif (
				!is_array($field['VALUE'])
				&& $field['VALUE'] <> ''
			)
			{
				$value = (int)$field['VALUE'];
			}

			if ($value !== false)
			{
				$this->addFilterInteger($rawFilterValue, [
					'FILTER_FIELD_NAME' => $field['FILTER_FIELD_NAME'],
					'FIELD_NAME' => $field['FIELD_NAME'],
					'OPERATION' => ($field['OPERATION'] ?? '='),
					'VALUE' => $value
				]);
			}
		}

		return $rawFilterValue;
	}
}