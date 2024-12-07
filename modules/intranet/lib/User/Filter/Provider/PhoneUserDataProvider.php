<?php

namespace Bitrix\Intranet\User\Filter\Provider;

use Bitrix\Main\Filter\EntityDataProvider;
use Bitrix\Main\Filter\UserSettings;
use Bitrix\Main\PhoneNumber\Format;
use Bitrix\Main\PhoneNumber\Parser;

class PhoneUserDataProvider extends EntityDataProvider
{
	private UserSettings $settings;
	private Parser $parser;

	public function __construct(UserSettings $settings)
	{
		$this->settings = $settings;
		$this->parser = Parser::getInstance();
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
				'FILTER_FIELD_NAME' => 'PHONE_MOBILE',
				'FIELD_NAME' => 'PERSONAL_MOBILE_FORMATTED',
				'VALUE' => $gridFilter['PHONE_MOBILE'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'PHONE',
				'FIELD_NAME' => 'PERSONAL_PHONE',
				'VALUE' => $gridFilter['PHONE'] ?? null
			],
			[
				'FILTER_FIELD_NAME' => 'WORK_PHONE',
				'FIELD_NAME' => 'WORK_PHONE',
				'VALUE' => $gridFilter['WORK_PHONE'] ?? null
			],
		];
	}

	private function addFilter(&$filter, array $params = []): void
	{
		$filterFieldName = ($params['FILTER_FIELD_NAME'] ?? '');
		$value = $this->getFormattedFilterValue($params['VALUE'] ?? '');

		if (
			$filterFieldName === ''
			|| empty($value)
			|| empty($params['OPERATION'])
		)
		{
			return;
		}

		$fieldName = (isset($params['FIELD_NAME']) && $params['FIELD_NAME'] <> '' ? $params['FIELD_NAME'] : $filterFieldName);
		$operation = $params['OPERATION'];

		unset($filter[$fieldName]);
		$filter[$operation . $fieldName] = $value;
	}

	public function prepareFilterValue(array $rawFilterValue): array
	{
		$filterValue = parent::prepareFilterValue($rawFilterValue);

		foreach ($this->getStringFieldList($filterValue) as $field)
		{
			if (is_array($field['VALUE']) && !empty($field['VALUE']))
			{
				$this->addFilter($filterValue, [
					'FILTER_FIELD_NAME' => $field['FILTER_FIELD_NAME'],
					'FIELD_NAME' => $field['FIELD_NAME'],
					'OPERATION' => '@',
					'VALUE' => $field['VALUE']
				]);
			}
			else if ($field['VALUE'] <> '')
			{
				$this->addFilter($filterValue, [
					'FILTER_FIELD_NAME' => $field['FILTER_FIELD_NAME'],
					'FIELD_NAME' => $field['FIELD_NAME'],
					'OPERATION' => '%=',
					'VALUE' => $field['VALUE']
				]);
			}
		}

		return $filterValue;
	}

	private function getFormattedFilterValue(string|array $filterValue): string|array
	{
		if (is_string($filterValue) && $filterValue <> '')
		{
			return $this->getFormatPhoneNumber($filterValue);
		}
		else if (is_array($filterValue) && !empty($filterValue))
		{
			$result = [];

			foreach ($filterValue as $phone)
			{
				$result[] = $this->getFormatPhoneNumber($phone);
			}

			return $result;
		}

		return $filterValue;
	}

	private function getFormatPhoneNumber(string $phone): string
	{
		$parsedPhoneNumber = $this->parser->parse($phone);

		return $parsedPhoneNumber->isValid()
			? $parsedPhoneNumber->format(Format::E164)
			: $phone;
	}
}