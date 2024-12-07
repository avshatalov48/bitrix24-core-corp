<?php

namespace Bitrix\BIConnector\Superset\UI\SettingsPanel\Field;

use Bitrix\BIConnector\Superset\Dashboard\EmbeddedFilter;

class PeriodFilterField extends EntityEditorField
{
	public const FIELD_NAME = 'FILTER_PERIOD';
	public const FIELD_ENTITY_EDITOR_TYPE = 'timePeriod';

	public function getFieldInitialData(): array
	{
		return static::getDefaultFilterData();
	}

	protected static function getDefaultFilterData(): array
	{
		return [
			'DATE_FILTER_START' => EmbeddedFilter\DateTime::getDefaultDateStart(),
			'DATE_FILTER_END' => EmbeddedFilter\DateTime::getDefaultDateEnd(),
			'FILTER_PERIOD' => EmbeddedFilter\DateTime::getDefaultPeriod(),
			'INCLUDE_LAST_FILTER_DATE' => EmbeddedFilter\DateTime::needIncludeDefaultLastFilterDate(),
		];
	}

	public function getName(): string
	{
		return static::FIELD_NAME;
	}

	public function getType(): string
	{
		return static::FIELD_ENTITY_EDITOR_TYPE;
	}

	protected static function getPeriodList(): array
	{
		$periods = [
			EmbeddedFilter\DateTime::PERIOD_LAST_7,
			EmbeddedFilter\DateTime::PERIOD_LAST_30,
			EmbeddedFilter\DateTime::PERIOD_LAST_90,
			EmbeddedFilter\DateTime::PERIOD_LAST_180,
			EmbeddedFilter\DateTime::PERIOD_LAST_365,
			EmbeddedFilter\DateTime::PERIOD_CURRENT_WEEK,
			EmbeddedFilter\DateTime::PERIOD_CURRENT_MONTH,
			EmbeddedFilter\DateTime::PERIOD_CURRENT_YEAR,
			EmbeddedFilter\DateTime::PERIOD_RANGE,
		];

		$items = [];
		foreach ($periods as $period)
		{
			$items[] = [
				'NAME' => EmbeddedFilter\DateTime::getPeriodName($period),
				'VALUE' => $period,
			];
		}

		return $items;
	}

	protected function getFieldInfoData(): array
	{
		return [
			'items' => static::getPeriodList(),
			'dateStartFieldName' => 'DATE_FILTER_START',
			'dateEndFieldName' => 'DATE_FILTER_END',
			'includeLastFilterDate' => 'INCLUDE_LAST_FILTER_DATE',
		];
	}
}
