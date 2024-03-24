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
			EmbeddedFilter\DateTime::PERIOD_WEEK,
			EmbeddedFilter\DateTime::PERIOD_MONTH,
			EmbeddedFilter\DateTime::PERIOD_QUARTER,
			EmbeddedFilter\DateTime::PERIOD_HALF_YEAR,
			EmbeddedFilter\DateTime::PERIOD_YEAR,
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
		];
	}
}
