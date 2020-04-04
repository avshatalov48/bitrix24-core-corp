<?php

namespace Bitrix\Crm\Integration\Report\Filter\Deal;

use Bitrix\Crm\Filter\DealSettings;
use Bitrix\Crm\Filter\Factory;
use Bitrix\Crm\Integration\Report\Dashboard\Sales\SalesDynamic;
use Bitrix\Crm\Integration\Report\Filter\Base as BaseFilter;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\DateType;

class SalesDynamicFilter extends BaseFilter
{
	/**
	 * @return array
	 */
	public static function getFieldsList()
	{
		$fieldsList = parent::getFieldsList();

		$userPermissions = \CCrmPerms::getCurrentUserPermissions();
		$dealFilter = Factory::createEntityFilter(
			new DealSettings(
				array(
					'ID' => SalesDynamic::BOARD_KEY,
					'categoryID' => -1,
					'categoryAccess' => array(
						'READ' => \CCrmDeal::getPermittedToReadCategoryIDs($userPermissions),
					),
					'flags' => DealSettings::FLAG_NONE
				)
			)
		);

		$fields = $dealFilter->getFields();

		$disabledFieldKeys = [
			'ACTIVITY_COUNTER',
			'TRACKING_SOURCE_ID',
			'TRACKING_CHANNEL_CODE',
		];

		foreach ($fields as $field)
		{
			$field = $field->toArray();

			if (in_array($field['id'], $disabledFieldKeys))
			{
				continue;
			}

			if ($field['id'] === 'CATEGORY_ID')
			{
				$field['params']['multiple'] = 'N';
			}

			$field['id'] = 'FROM_DEAL_'.$field['id'];
			$field['name'] = $field['name'].' '.Loc::getMessage('CRM_REPORT_SALES_DYNAMIC_BOARD_FILTER_DEAL_FIELDS_POSTFIX');
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
		$presets = parent::getPresetsList();

		$presets['filter_last_30_day'] = [
			'name' => Loc::getMessage('CRM_REPORT_SALES_DYNAMIC_LAST_30_DAYS_FILTER_PRESET_TITLE'),
			'fields' => array(
				'TIME_PERIOD_datesel' => DateType::LAST_30_DAYS,
				'FROM_DEAL_CATEGORY_ID' => "0"
			),
			'default' => true,
		];

		return $presets;
	}

}