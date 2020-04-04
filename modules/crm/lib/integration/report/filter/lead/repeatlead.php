<?php

namespace Bitrix\Crm\Integration\Report\Filter\Lead;

use Bitrix\Crm\Integration\Report\Dashboard\LeadAnalytic\RepeatLead as RepeatLeadBoard;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\DateType;

/**
 * Class RepeatLead
 * @package Bitrix\Crm\Integration\Report\Filter\Lead
 */
class RepeatLead extends Base
{
	/**
	 * @return string
	 */
	protected static function getBoardKey()
	{
		return RepeatLeadBoard::BOARD_KEY;
	}

	/**
	 * @return array
	 */
	public static function getPresetsList()
	{
		$presets = parent::getPresetsList();

		$presets['filter_repeat_leads'] = [
			'name' => Loc::getMessage('CRM_REPORT_FILTER_REPEATED_LEAD_LAST_30_DAYS_PRESET_TITLE'),
			'fields' => [
				'TIME_PERIOD_datesel' => DateType::LAST_30_DAYS,
				'FROM_LEAD_IS_RETURN_CUSTOMER' => 'Y',
			],
			'default' => true,
		];

		$presets['filter_current_month'] = [
			'name' => Loc::getMessage('CRM_REPORT_FILTER_REPEATED_LEAD_CURRENT_MONTH_DAYS_PRESET_TITLE'),
			'fields' => array(
				'TIME_PERIOD_datesel' => DateType::CURRENT_MONTH,
				'FROM_LEAD_IS_RETURN_CUSTOMER' => 'Y',
			),
		];

		$presets['filter_last_month'] = [
			'name' => Loc::getMessage('CRM_REPORT_FILTER_REPEATED_LEAD_LAST_MONTH_DAYS_PRESET_TITLE'),
			'fields' => array(
				'TIME_PERIOD_datesel' => DateType::LAST_MONTH,
				'FROM_LEAD_IS_RETURN_CUSTOMER' => 'Y',
			),
		];

		return $presets;
	}
}