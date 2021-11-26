<?php

namespace Bitrix\Crm\Integration\Report\Filter;

use Bitrix\Crm\Filter\DealSettings;
use Bitrix\Crm\Filter\Factory;
use Bitrix\Crm\Filter\LeadSettings;
use Bitrix\Crm\Integration\Report\Dashboard\Sales\SalesFunnelBoard;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UI\Filter\DateType;

/**
 * Class SalesFunnelFilter
 * @package Bitrix\Crm\Integration\Report\Filter
 */
class SalesFunnelFilter extends Base
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
		$fieldsList = parent::getFieldsList();

		if (self::$fieldList)
		{
			return self::$fieldList;
		}
		if (\Bitrix\Crm\Settings\LeadSettings::isEnabled())
		{
			$leadFilter = Factory::createEntityFilter(
				new LeadSettings(['ID' => SalesFunnelBoard::BOARD_KEY])
			);

			$disabledFieldKeys = [
				'STATUS_CONVERTED',
				'ACTIVITY_COUNTER',
				'COMMUNICATION_TYPE',
				'WEB',
				'IM',
				'TRACKING_SOURCE_ID',
				'TRACKING_CHANNEL_CODE',
				'PRODUCT_ROW_PRODUCT_ID',
				'STATUS_ID_FROM_HISTORY',
				'STATUS_ID_FROM_SUPPOSED_HISTORY',
				'STATUS_SEMANTIC_ID_FROM_HISTORY',
				'COMMENTS',
			];

			$fields = $leadFilter->getFields();
			foreach ($fields as $field)
			{
				$field = $field->toArray();

				if (in_array($field['id'], $disabledFieldKeys))
				{
					continue;
				}

				$field['id'] = 'FROM_LEAD_'.$field['id'];
				$field['name'] =
					$field['name']
					. ' '
					. Loc::getMessage('CRM_REPORT_SALES_FUNNEL_BOARD_FILTER_LEAD_FIELDS_POSTFIX')
				;
				if (isset($field['type']) && $field['type'] === 'custom_entity')
				{
					$field['html'] = str_replace(
						$field['selector']['DATA']['FIELD_ID'],
						'FROM_LEAD_'.$field['selector']['DATA']['FIELD_ID'],
						$field['html']
					);
					$field['html'] = str_replace(
						$field['selector']['DATA']['ID'],
						'from_lead_'.$field['selector']['DATA']['ID'],
						$field['html']
					);
					$field['selector']['DATA']['ID'] = 'from_lead_'.$field['selector']['DATA']['ID'];
					$field['selector']['DATA']['FIELD_ID'] = 'FROM_LEAD_'.$field['selector']['DATA']['FIELD_ID'];
				}
				$fieldsList[] = $field;
			}
		}
		$userPermissions = \CCrmPerms::getCurrentUserPermissions();
		$dealFilter = Factory::createEntityFilter(
			new DealSettings(
				[
					'ID' => SalesFunnelBoard::BOARD_KEY,
					'categoryID' => -1,
					'categoryAccess' => [
						'READ' => \CCrmDeal::getPermittedToReadCategoryIDs($userPermissions),
					],
					'flags' => DealSettings::FLAG_NONE
				]
			)
		);

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

		$fields = $dealFilter->getFields();
		foreach ($fields as $field)
		{
			$field = $field->toArray();
			//TODO: HACK: add this field after
			if ($field['id'] === 'ACTIVITY_COUNTER')
			{
				continue;
			}

			if (in_array($field['id'], $disabledFieldKeys))
			{
				continue;
			}

			if ($field['id'] === 'CATEGORY_ID')
			{
				$field['params']['multiple'] = 'N';
			}

			$field['id'] = 'FROM_DEAL_'.$field['id'];
			$field['name'] = $field['name'].
								' '.
								Loc::getMessage('CRM_REPORT_SALES_FUNNEL_BOARD_FILTER_DEAL_FIELDS_POSTFIX');
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

			if ($field['id'] === 'FROM_DEAL_CATEGORY_ID')
			{
				$field['STRICT'] = true;
			}

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
		$presets['filter_last_30_day'] = [
			'name' => Loc::getMessage('CRM_REPORT_FILTER_LAST_30_DAYS_PRESET_TITLE'),
			'fields' => [
				'TIME_PERIOD_datesel' => DateType::LAST_30_DAYS,
				'FROM_DEAL_CATEGORY_ID' => "0"
			],
			'default' => true,
		];

		$presets['filter_current_month'] = [
			'name' => Loc::getMessage('CRM_REPORT_FILTER_CURRENT_MONTH_PRESET_TITLE'),
			'fields' => [
				'TIME_PERIOD_datesel' => DateType::CURRENT_MONTH,
				'FROM_DEAL_CATEGORY_ID' => "0"
			],
		];

		$presets['filter_last_month'] = [
			'name' => Loc::getMessage('CRM_REPORT_FILTER_LAST_MONTH_PRESET_TITLE'),
			'fields' => [
				'TIME_PERIOD_datesel' => DateType::LAST_MONTH,
				'FROM_DEAL_CATEGORY_ID' => "0"
			],
		];

		return $presets;
	}

}