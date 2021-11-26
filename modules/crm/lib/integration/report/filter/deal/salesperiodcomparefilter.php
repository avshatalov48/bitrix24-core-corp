<?php

namespace Bitrix\Crm\Integration\Report\Filter\Deal;

use Bitrix\Crm\Filter\DealSettings;
use Bitrix\Crm\Filter\Factory;
use Bitrix\Crm\Integration\Report\Dashboard\Sales\SalesDynamic;
use Bitrix\Crm\Integration\Report;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\DateType;

class SalesPeriodCompareFilter extends Report\Filter\Base
{
	/**
	 * @return array
	 */
	public static function getFieldsList()
	{
		\CJSCore::Init(["crm.report.salescompareperiods"]);

		$fieldsList = parent::getFieldsList();

		$fieldsList['PREVIOUS_PERIOD'] = [
			'id' => 'PREVIOUS_PERIOD',
			'name' => Loc::getMessage("CRM_REPORT_SALES_PERIOD_COMPARE_PREVIOUS_PERIOD"),
			'type' => 'date',
			'default' => true,
			'required' => true,
			'valueRequired' => true,
			'exclude' => [
				DateType::NONE,
				DateType::CURRENT_DAY,
				DateType::YESTERDAY,
				DateType::TOMORROW,
				DateType::NEXT_DAYS,
				DateType::NEXT_WEEK,
				DateType::NEXT_MONTH,
				DateType::EXACT,
			]
		];

		$userPermissions = \CCrmPerms::getCurrentUserPermissions();
		$dealFilter = Factory::createEntityFilter(
			new DealSettings(
				[
					'ID' => SalesDynamic::BOARD_KEY,
					'categoryID' => -1,
					'categoryAccess' => [
						'READ' => \CCrmDeal::getPermittedToReadCategoryIDs($userPermissions),
					],
					'flags' => DealSettings::FLAG_NONE
				]
			)
		);

		$fields = $dealFilter->getFields();

		$disabledFieldKeys = [
			'ACTIVITY_COUNTER',
			'TRACKING_SOURCE_ID',
			'TRACKING_CHANNEL_CODE',
			'PRODUCT_ROW_PRODUCT_ID',
			'STAGE_SEMANTIC_ID',
			'STAGE_ID_FROM_HISTORY',
			'STAGE_ID_FROM_SUPPOSED_HISTORY',
			'STAGE_SEMANTIC_ID_FROM_HISTORY',
			'COMMENTS',
		];

		foreach ($fields as $field)
		{
			$field = $field->toArray();

			if (in_array($field['id'], $disabledFieldKeys))
			{
				continue;
			}

			//TODO dates fields isn't work with time period
			if ($field['type'] === 'date')
			{
				continue;
			}

			if ($field['id'] === 'CATEGORY_ID')
			{
				$field['params']['multiple'] = 'N';
			}

			$field['id'] = 'FROM_DEAL_'.$field['id'];
			//$field['name'] = $field['name'] . ' ' . Loc::getMessage('CRM_REPORT_SALES_DYNAMIC_BOARD_FILTER_DEAL_FIELDS_POSTFIX');
			if (isset($field['type']) && $field['type'] === 'custom_entity')
			{
				$field['html'] = str_replace(
					$field['selector']['DATA']['FIELD_ID'],
					'FROM_DEAL_'.$field['selector']['DATA']['FIELD_ID'],
					$field['html']
				);
				$field['html'] = str_replace(
					$field['selector']['DATA']['ID'],
					'from_deal_'.$field['selector']['DATA']['ID'],
					$field['html']
				);
				$field['selector']['DATA']['ID'] = 'from_deal_'.$field['selector']['DATA']['ID'];
				$field['selector']['DATA']['FIELD_ID'] = 'FROM_DEAL_'.$field['selector']['DATA']['FIELD_ID'];
			}
			$fieldsList[] = $field;
		}

		return $fieldsList;
	}

	/**
	 * @return array
	 */
	public static function getPresetsList()
	{
		$presets = [];

		$presets['filter_last_month'] = [
			'name' => Loc::getMessage('REPORT_BOARD_CURRENT_MONTH_PRESET_TITLE'),
			'fields' => [
				'TIME_PERIOD_datesel' => DateType::CURRENT_MONTH,
				'PREVIOUS_PERIOD_datesel' => DateType::LAST_MONTH,
				'FROM_DEAL_CATEGORY_ID' => "0",
			],
			'default' => true,
		];

		return $presets;
	}

	public function getStringList()
	{
		$result = parent::getStringList();
		$result[] = "<script>BX.ready(function (){BX.Crm.Report.Dashboard.Content.SalesComparePeriods.init('".\CUtil::JSEscape($this->getFilterId())."')});</script>";
		return $result;
	}

}