<?php

namespace Bitrix\Crm\Integration\Report\Filter;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Tracking;

Loc::loadMessages(__FILE__);

/**
 * Class LeadAnalyticsFilter
 * @package Bitrix\Crm\Integration\Report\Filter
 */
class TrafficEffectFilter extends Base
{
	/**
	 * Get fields list.
	 *
	 * @return array
	 */
	public static function getFieldsList()
	{
		$fieldsList = parent::getFieldsList();

		$sources = [];
		foreach (Tracking\Provider::getActualSources() as $row)
		{
			if (empty($row['ID']))
			{
				continue;
			}

			$sources[$row['ID']] = $row['NAME'];
		}

		$fieldsList[] = [
			'id' => 'SOURCE_CODE',
			"name" => Loc::getMessage('CRM_REPORT_TRAFFIC_EFFECT_FILTER_SOURCE_FIELD_TITLE'),
			'params' => array('multiple' => 'Y'),
			'default' => true,
			'type' => 'list',
			'items' => $sources
		];

		return $fieldsList;
	}

	/**
	 * Get filter parameters.
	 *
	 * @return array
	 */
	public function getFilterParameters()
	{
		$parameters = parent::getFilterParameters();

		$parameters['VALUE_REQUIRED_MODE '] = true;

		return $parameters;
	}

	/**
	 * @return array
	 */
	public static function getPresetsList()
	{
		return [
			'crm_analytics_period_last_30' => [
				'name' => Loc::getMessage('CRM_REPORT_TRAFFIC_EFFECT_FILTER_LAST_30_DAYS_PRESET_TITLE'),
				'default' => true,
				'fields' => [
					'TIME_PERIOD_datesel' => \Bitrix\Main\UI\Filter\DateType::LAST_30_DAYS,
				]
			],
			'crm_analytics_period_curr_month' => [
				'name' => Loc::getMessage('CRM_REPORT_TRAFFIC_EFFECT_FILTER_CURRENT_MONTH_PRESET_TITLE'),
				'fields' => [
					'TIME_PERIOD_datesel' => \Bitrix\Main\UI\Filter\DateType::CURRENT_MONTH,
				]
			],
		];
	}
}