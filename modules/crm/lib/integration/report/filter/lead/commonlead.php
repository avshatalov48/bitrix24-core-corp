<?php

namespace Bitrix\Crm\Integration\Report\Filter\Lead;

use Bitrix\Crm\Integration\Report\Dashboard\LeadAnalytic\CommonLead as CommonLeadBoard;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\DateType;

/**
 * Class CommonLead
 * @package Bitrix\Crm\Integration\Report\Filter\Lead
 */
class CommonLead extends Base
{
	/**
	 * @return string
	 */
	protected static function getBoardKey()
	{
		return CommonLeadBoard::BOARD_KEY;
	}

	/**
	 * @return array
	 */
	public static function getPresetsList()
	{
		$presets = parent::getPresetsList();

		$presets['filter_last_30_day'] = [
			'name' => Loc::getMessage('CRM_REPORT_FILTER_LAST_30_DAYS_PRESET_TITLE'),
			'fields' => array(
				'TIME_PERIOD_datesel' => DateType::LAST_30_DAYS
			),
			'default' => true,
		];

		$presets['filter_current_month'] = [
			'name' => Loc::getMessage('CRM_REPORT_FILTER_CURRENT_MONTH_PRESET_TITLE'),
			'fields' => array(
				'TIME_PERIOD_datesel' => DateType::CURRENT_MONTH,
			),
		];

		$presets['filter_last_month'] = [
			'name' => Loc::getMessage('CRM_REPORT_FILTER_LAST_MONTH_PRESET_TITLE'),
			'fields' => array(
				'TIME_PERIOD_datesel' => DateType::LAST_MONTH,
			),
		];
		return $presets;
	}
}