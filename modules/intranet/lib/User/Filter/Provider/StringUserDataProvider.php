<?php

namespace Bitrix\Intranet\User\Filter\Provider;

use Bitrix\Main\Filter\EntityDataProvider;
use Bitrix\Main\Filter\UserSettings;

class StringUserDataProvider extends EntityDataProvider
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

	private function getStringFieldList($gridFilter): array
	{
		return [
			[
				'FILTER_FIELD_NAME' => 'NAME',
				'FIELD_NAME' => 'NAME',
				'OPERATION' => '%=',
				'VALUE' => ($gridFilter['NAME'] ?? null).'%'
			],
			[
				'FILTER_FIELD_NAME' => 'LAST_NAME',
				'FIELD_NAME' => 'LAST_NAME',
				'OPERATION' => '%=',
				'VALUE' => ($gridFilter['LAST_NAME'] ?? null).'%'
			],
			[
				'FILTER_FIELD_NAME' => 'SECOND_NAME',
				'FIELD_NAME' => 'SECOND_NAME',
				'OPERATION' => '%=',
				'VALUE' => ($gridFilter['SECOND_NAME'] ?? null).'%'
			],
			[
				'FILTER_FIELD_NAME' => 'LOGIN',
				'FIELD_NAME' => 'LOGIN',
				'OPERATION' => '%=',
				'VALUE' => ($gridFilter['LOGIN'] ?? null).'%'
			],
			[
				'FILTER_FIELD_NAME' => 'EMAIL',
				'FIELD_NAME' => 'EMAIL',
				'OPERATION' => '%=',
				'VALUE' => ($gridFilter['EMAIL'] ?? null).'%'
			],
			[
				'FILTER_FIELD_NAME' => 'GENDER',
				'FIELD_NAME' => 'PERSONAL_GENDER',
				'OPERATION' => '=',
				'VALUE' => $gridFilter['GENDER'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'PERSONAL_CITY',
				'FIELD_NAME' => 'PERSONAL_CITY',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['PERSONAL_CITY'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'PERSONAL_STREET',
				'FIELD_NAME' => 'PERSONAL_STREET',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['PERSONAL_STREET'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'PERSONAL_STATE',
				'FIELD_NAME' => 'PERSONAL_STATE',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['PERSONAL_STATE'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'PERSONAL_ZIP',
				'FIELD_NAME' => 'PERSONAL_ZIP',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['PERSONAL_ZIP'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'PERSONAL_MAILBOX',
				'FIELD_NAME' => 'PERSONAL_MAILBOX',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['PERSONAL_MAILBOX'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'WORK_CITY',
				'FIELD_NAME' => 'WORK_CITY',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['WORK_CITY'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'WORK_STREET',
				'FIELD_NAME' => 'WORK_STREET',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['WORK_STREET'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'WORK_STATE',
				'FIELD_NAME' => 'WORK_STATE',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['WORK_STATE'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'WORK_ZIP',
				'FIELD_NAME' => 'WORK_ZIP',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['WORK_ZIP'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'WORK_MAILBOX',
				'FIELD_NAME' => 'WORK_MAILBOX',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['WORK_MAILBOX'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'POSITION',
				'FIELD_NAME' => 'WORK_POSITION',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['POSITION'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'COMPANY',
				'FIELD_NAME' => 'WORK_COMPANY',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['COMPANY'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'WORK_DEPARTMENT',
				'FIELD_NAME' => 'WORK_DEPARTMENT',
				'OPERATION' => '%',
				'VALUE' => $gridFilter['WORK_DEPARTMENT'] ?? null
			],
		];
	}

	private function addFilterString(&$filter, array $params = [])
	{
		$filterFieldName = ($params['FILTER_FIELD_NAME'] ?? '');
		$value = ($params['VALUE'] ?? '');

		if (
			$filterFieldName == ''
			|| trim($value, '%') == ''
		)
		{
			return;
		}

		$fieldName = (isset($params['FIELD_NAME']) && $params['FIELD_NAME'] <> '' ? $params['FIELD_NAME'] : $filterFieldName);
		$operation = ($params['OPERATION'] ?? '%=');

		unset($filter[$fieldName]);
		$filter[$operation . $fieldName] = $value;
	}

	public function prepareFilterValue(array $rawFilterValue): array
	{
		$filterValue = parent::prepareFilterValue($rawFilterValue);

		foreach ($this->getStringFieldList($filterValue) as $field)
		{
			if ($field['VALUE'] <> '')
			{
				$this->addFilterString($filterValue, [
					'FILTER_FIELD_NAME' => $field['FILTER_FIELD_NAME'],
					'FIELD_NAME' => $field['FIELD_NAME'],
					'OPERATION' => ($field['OPERATION'] ?? '%='),
					'VALUE' => $field['VALUE']
				]);
			}
		}

		return $filterValue;
	}
}