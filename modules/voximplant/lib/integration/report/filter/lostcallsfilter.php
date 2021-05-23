<?php

namespace Bitrix\Voximplant\Integration\Report\Filter;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\DateType;

/**
 * Class LostCallsFilter
 * @package Bitrix\Voximplant\Integration\Report\Filter
 */
class LostCallsFilter extends Base
{
	public static function getFieldsList(): array
	{
		$fieldsList = parent::getFieldsList();

		$fieldsList['TIME_PERIOD']['required'] = true;
		$fieldsList['TIME_PERIOD']['valueRequired'] = true;
		$fieldsList['TIME_PERIOD']['exclude'] = [
			DateType::NONE,
			DateType::CURRENT_DAY,
			DateType::YESTERDAY,
			DateType::TOMORROW,
			DateType::NEXT_DAYS,
			DateType::NEXT_WEEK,
			DateType::NEXT_MONTH,
			DateType::EXACT,
		];

		unset($fieldsList['PORTAL_NUMBER']);

		return $fieldsList;
	}

	/**
	 * Returns presets for the filter.
	 *
	 * @return array
	 */
	public static function getPresetsList(): array
	{
		$presets['filter_last_30_days'] = [
			'name' => Loc::getMessage('TELEPHONY_REPORT_LAST_30_DAYS_FILTER_PRESET_TITLE'),
			'fields' => [
				'TIME_PERIOD_datesel' => DateType::LAST_30_DAYS,
			],
			'default' => true,
		];


		$presets['filter_current_month'] = [
			'name' => Loc::getMessage('TELEPHONY_REPORT_CURRENT_MONTH_FILTER_PRESET_TITLE'),
			'fields' => [
				'TIME_PERIOD_datesel' => DateType::CURRENT_MONTH,
			],
			'default' => false,
		];

		$presets['filter_current_quarter'] = [
			'name' => Loc::getMessage('TELEPHONY_REPORT_CURRENT_QUARTER_FILTER_PRESET_TITLE'),
			'fields' => [
				'TIME_PERIOD_datesel' => DateType::CURRENT_QUARTER,
			],
			'default' => false,
		];

		return $presets;
	}
}