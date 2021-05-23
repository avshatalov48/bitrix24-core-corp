<?php

namespace Bitrix\Voximplant\Integration\Report\Filter;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\DateType;

/**
 * Class AverageCallTimeFilter
 * @package Bitrix\Voximplant\Integration\Report\Filter
 */
class AverageCallTimeFilter extends Base
{
	/**
	 * Returns the filter fields.
	 *
	 * @return array
	 */
	public static function getFieldsList(): array
	{
		$fieldsList = parent::getFieldsList();

		unset($fieldsList['PHONE_NUMBER']);

		return $fieldsList;
	}

	/**
	 * Returns presets for the filter.
	 *
	 * @return array
	 */
	public static function getPresetsList(): array
	{
		$presets['filter_current_month'] = [
			'name' => Loc::getMessage('TELEPHONY_REPORT_CURRENT_MONTH_FILTER_PRESET_TITLE'),
			'fields' => [
				'TIME_PERIOD_datesel' => DateType::CURRENT_MONTH,
			],
			'default' => true,
		];

		$presets['filter_current_quarter'] = [
			'name' => Loc::getMessage('TELEPHONY_REPORT_CURRENT_QUARTER_FILTER_PRESET_TITLE'),
			'fields' => [
				'TIME_PERIOD_datesel' => DateType::CURRENT_QUARTER,
			],
			'default' => false,
		];

		$presets['filter_current_year'] = [
			'name' => Loc::getMessage('TELEPHONY_REPORT_CURRENT_YEAR_FILTER_PRESET_TITLE'),
			'fields' => [
				'TIME_PERIOD_datesel' => DateType::YEAR,
			],
			'default' => false,
		];

		return $presets;
	}
}