<?php

namespace Bitrix\Crm\Integration\Report\Filter\Lead;

use Bitrix\Crm\Integration\Report\Dashboard\LeadAnalytic\NewLead as NewLeadBoard;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\DateType;

/**
 * Class NewLead
 * @package Bitrix\Crm\Integration\Report\Filter\Lead
 */
class NewLead extends Base
{
	/**
	 * @return string
	 */
	protected static function getBoardKey()
	{
		return NewLeadBoard::BOARD_KEY;
	}

	/**
	 * @return array
	 */
	public static function getPresetsList()
	{
		$presets = parent::getPresetsList();

		$presets['filter_new_lead'] = [
			'name' => Loc::getMessage('CRM_REPORT_FILTER_NEW_LEAD_LAST_30_DAYS_PRESET_TITLE'),
			'fields' => array(
				'TIME_PERIOD_datesel' => DateType::LAST_30_DAYS,
				'FROM_LEAD_IS_RETURN_CUSTOMER' => 'N',
			),
			'default' => true,
		];

		$presets['filter_current_month'] = [
			'name' => Loc::getMessage('CRM_REPORT_FILTER_NEW_LEAD_CURRENT_MONTH_PRESET_TITLE'),
			'fields' => array(
				'TIME_PERIOD_datesel' => DateType::CURRENT_MONTH,
				'FROM_LEAD_IS_RETURN_CUSTOMER' => 'N',
			),
		];

		$presets['filter_last_month'] = [
			'name' => Loc::getMessage('CRM_REPORT_FILTER_NEW_LEAD_LAST_MONTH_PRESET_TITLE'),
			'fields' => array(
				'TIME_PERIOD_datesel' => DateType::LAST_MONTH,
				'FROM_LEAD_IS_RETURN_CUSTOMER' => 'N',
			),
		];

		return $presets;
	}
}