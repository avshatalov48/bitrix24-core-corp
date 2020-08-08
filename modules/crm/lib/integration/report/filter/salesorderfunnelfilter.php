<?php

namespace Bitrix\Crm\Integration\Report\Filter;

use Bitrix\Crm\Filter\OrderSettings;
use Bitrix\Crm\Filter\Factory;
use Bitrix\Crm\Integration\Report\Dashboard\ShopReports\SalesOrderFunnelBoard;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\DateType;

/**
 * Class SalesOrderFunnelFilter
 * @package Bitrix\Crm\Integration\Report\Filter
 */
class SalesOrderFunnelFilter extends Base
{
	static $fieldList = [];

	public function getFilterParameters()
	{
		$filterParameters = parent::getFilterParameters();
		$filterParameters['RESET_TO_DEFAULT_MODE'] = true;
		$filterParameters['DISABLE_SEARCH'] = false;
		$filterParameters['ENABLE_LIVE_SEARCH'] = true;

		return $filterParameters;
	}

	/**
	 * @return array
	 */
	public static function getFieldsList()
	{
		if (self::$fieldList)
		{
			return self::$fieldList;
		}

		$orderFilter = Factory::createEntityFilter(
			new OrderSettings(
				[
					'ID' => SalesOrderFunnelBoard::BOARD_KEY,
				]
			)
		);

		$fields = $orderFilter->getFields();
		foreach ($fields as $field)
		{
			$field = $field->toArray();
			$field['id'] = 'FROM_ORDER_'.$field['id'];
			$fieldsList[] = $field;
		}
		self::$fieldList = $fieldsList;

		return self::$fieldList;
	}

	/**
	 * @return array
	 */
	public static function getPresetsList()
	{
		$presets['filter_1_last_30_day'] = [
			'name' => Loc::getMessage('CRM_REPORT_FILTER_LAST_30_DAYS_PRESET_TITLE'),
			'fields' => [
				'FROM_ORDER_DATE_INSERT_datesel' => DateType::LAST_30_DAYS
			],
			'default' => true,
		];

		$presets['filter_2_current_month'] = [
			'name' => Loc::getMessage('CRM_REPORT_FILTER_CURRENT_MONTH_PRESET_TITLE'),
			'fields' => [
				'FROM_ORDER_DATE_INSERT_datesel' => DateType::CURRENT_MONTH
			],
		];

		$presets['filter_3_last_month'] = [
			'name' => Loc::getMessage('CRM_REPORT_FILTER_LAST_MONTH_PRESET_TITLE'),
			'fields' => [
				'FROM_ORDER_DATE_INSERT_datesel' => DateType::LAST_MONTH
			],
		];

		return $presets;
	}

}