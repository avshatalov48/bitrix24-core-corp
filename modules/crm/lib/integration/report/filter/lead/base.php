<?php

namespace Bitrix\Crm\Integration\Report\Filter\Lead;

use Bitrix\Crm\Filter\Factory;
use Bitrix\Crm\Filter\LeadSettings;
use Bitrix\Crm\Integration\Report\Dashboard\LeadAnalyticBoard;
use Bitrix\Crm\Integration\Report\Filter\Base as BaseFilter;

/**
 * Class Base
 * @package Bitrix\Crm\Integration\Report\Filter\Lead
 */
class Base extends BaseFilter
{
	public function getFilterParameters()
	{
		$params = parent::getFilterParameters();
		$params['RESET_TO_DEFAULT_MODE'] = true;
		$params['DISABLE_SEARCH'] = false;
		$params['ENABLE_LIVE_SEARCH'] = true;
		return $params;
	}

	/**
	 * @return string
	 */
	protected static function getBoardKey()
	{
		return '';
	}

	/**
	 * @return array
	 */
	public static function getFieldsList()
	{
		$fieldsList = parent::getFieldsList();
		$leadFilter = Factory::createEntityFilter(
			new LeadSettings(array('ID' => static::getBoardKey()))
		);

		$fields = $leadFilter->getFields();
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
		foreach ($fields as $field)
		{
			$field = $field->toArray();

			if (in_array($field['id'], $disabledFieldKeys))
			{
				continue;
			}


			$field['id'] = 'FROM_LEAD_'.$field['id'];
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

		return $fieldsList;
	}

	public static function getPresetsList()
	{
		return [];
	}

}