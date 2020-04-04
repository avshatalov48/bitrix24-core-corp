<?php

namespace Bitrix\Crm\Integration\Report\Filter;

use Bitrix\Crm\Widget\FilterPeriodType;
use Bitrix\Main\Localization\Loc;
use Bitrix\Report\VisualConstructor\Helper\Filter;
use Bitrix\Main\UI\Filter\DateType;

class MyReportsFilter extends Filter
{
	public static function createFilterId($boardId)
	{
		return $boardId;
	}

	public static function getFieldsList()
	{
		return [
			[
				'id' => 'RESPONSIBLE_ID',
				'name' => Loc::getMessage("CRM_REPORT_FILTER_MY_REPORTS_RESPONSIBLE"),
				'default' => true,
				'type' => 'dest_selector',
				'params' => [
					'context' => 'CRM_WIDGET_FILTER_RESPONSIBLE_ID',
					'multiple' => 'N',
					'contextCode' => 'U',
					'enableAll' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'prefix' => 'U',
				]
			],
			[
				'id' => 'PERIOD',
				'name' => Loc::getMessage("CRM_REPORT_FILTER_MY_REPORTS_PERIOD"),
				'default' => true,
				'type' => 'date',
				'exclude' => [
					DateType::NONE,
					DateType::CURRENT_DAY,
					DateType::CURRENT_WEEK,
					DateType::YESTERDAY,
					DateType::TOMORROW,
					DateType::PREV_DAYS,
					DateType::NEXT_DAYS,
					DateType::NEXT_WEEK,
					DateType::NEXT_MONTH,
					DateType::LAST_MONTH,
					DateType::LAST_WEEK,
					DateType::EXACT,
					DateType::RANGE
				]
			]
		];
	}

	public static function getPresetsList()
	{
		$monthPresetFilter = [];
		\Bitrix\Crm\Widget\Filter::addDateType(
			$monthPresetFilter,
			'PERIOD',
			FilterPeriodType::convertToDateType(FilterPeriodType::CURRENT_MONTH)
		);

		$quarterPresetFilter = [];
		\Bitrix\Crm\Widget\Filter::addDateType(
			$quarterPresetFilter,
			'PERIOD',
			FilterPeriodType::convertToDateType(FilterPeriodType::CURRENT_QUARTER)
		);

		return [
			'filter_current_month' => [
				'name' => FilterPeriodType::getDescription(FilterPeriodType::CURRENT_MONTH),
				'fields' => $monthPresetFilter,
				'default' => true,
			],
			'filter_current_quarter' => [
				'name' => FilterPeriodType::getDescription(FilterPeriodType::CURRENT_QUARTER),
				'fields' => $quarterPresetFilter
			]
		];
	}

}