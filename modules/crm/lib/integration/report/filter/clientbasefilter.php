<?php

namespace Bitrix\Crm\Integration\Report\Filter;

use Bitrix\Crm\Filter\CompanySettings;
use Bitrix\Crm\Filter\ContactSettings;
use Bitrix\Crm\Filter\Factory;
use Bitrix\Crm\Integration\Report\EventHandler;
use Bitrix\Main\Localization\Loc;

/**
 * Class ClientBaseFilter
 * @package Bitrix\Crm\Integration\Report\Filter
 */
class ClientBaseFilter extends Base
{
	/**
	 * @return array
	 */
	public static function getFieldsList()
	{
		$fieldsList = parent::getFieldsList();;

		$contactFilter = Factory::createEntityFilter(
			new ContactSettings(array('ID' => EventHandler::CLIENT_BASE_BOARD_KEY))
		);

		$fields = $contactFilter->getFields();
		foreach ($fields as $field)
		{
			$field = $field->toArray();
			$field['id'] = 'FROM_CONTACT_'.$field['id'];
			$field['name'] = $field['name'].' '.Loc::getMessage('CRM_REPORT_CLIENT_BASE_BOARD_FILTER_CONTACT_FIELDS_POSTFIX');
			$fieldsList[] = $field;
		}

		$companyFilter = Factory::createEntityFilter(
			new CompanySettings(array('ID' => EventHandler::CLIENT_BASE_BOARD_KEY))
		);

		$fields = $companyFilter->getFields();
		foreach ($fields as $field)
		{
			$field = $field->toArray();
			$field['id'] = 'FROM_COMPANY_'.$field['id'];
			$field['name'] = $field['name'].' '.Loc::getMessage('CRM_REPORT_CLIENT_BASE_BOARD_FILTER_COMPANY_FIELDS_POSTFIX');
			$fieldsList[] = $field;
		}

		return $fieldsList;
	}

	/**
	 * @return array
	 */
	public static function getPresetsList()
	{
		return [];
	}
}