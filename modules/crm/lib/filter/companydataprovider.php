<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

use Bitrix\Crm;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Counter\EntityCounterType;

Loc::loadMessages(__FILE__);

class CompanyDataProvider extends EntityDataProvider
{
	/** @var CompanySettings|null */
	protected $settings = null;

	function __construct(CompanySettings $settings)
	{
		$this->settings = $settings;
	}

	/**
	 * Get Settings
	 * @return CompanySettings
	 */
	public function getSettings()
	{
		return $this->settings;
	}

	/**
	 * Get specified entity field caption.
	 * @param string $fieldID Field ID.
	 * @return string
	 */
	protected function getFieldName($fieldID)
	{
		$name = Loc::getMessage("CRM_COMPANY_FILTER_{$fieldID}");
		if($name === null)
		{
			$name = \CCrmCompany::GetFieldCaption($fieldID);
		}

		return $name;
	}

	/**
	 * Prepare field list.
	 * @return Field[]
	 */
	public function prepareFields()
	{
		$result =  array(
			'ID' => $this->createField('ID'),
			'TITLE' => $this->createField('TITLE'),
			'DATE_CREATE' => $this->createField(
				'DATE_CREATE',
				array('type' => 'date', 'default' => true)
			),
			'DATE_MODIFY' => $this->createField(
				'DATE_MODIFY',
				array('type' => 'date')
			),
			'ASSIGNED_BY_ID' => $this->createField(
				'ASSIGNED_BY_ID',
				array('type' => 'dest_selector', 'default' => true, 'partial' => true)
			),
			'CREATED_BY_ID' => $this->createField(
				'CREATED_BY_ID',
				array('type' => 'dest_selector', 'partial' => true)
			),
			'MODIFY_BY_ID' => $this->createField(
				'MODIFY_BY_ID',
				array('type' => 'dest_selector', 'partial' => true)
			),
			'ACTIVITY_COUNTER' => $this->createField(
				'ACTIVITY_COUNTER',
				array('type' => 'list', 'default' => true, 'partial' => true)
			),
			'COMMUNICATION_TYPE' => $this->createField(
				'COMMUNICATION_TYPE',
				array('type' => 'list', 'default' => true, 'partial' => true)
			),
			'HAS_PHONE' => $this->createField(
				'HAS_PHONE',
				array('type' => 'checkbox')
			),
			'PHONE' => $this->createField('PHONE'),
			'HAS_EMAIL' => $this->createField(
				'HAS_EMAIL',
				array('type' => 'checkbox')
			),
			'EMAIL' => $this->createField('EMAIL'),
			'WEB' => $this->createField('WEB'),
			'IM' => $this->createField('IM'),
			'COMPANY_TYPE' => $this->createField(
				'COMPANY_TYPE',
				array('type' => 'list', 'default' => true, 'partial' => true)
			),
			'INDUSTRY' => $this->createField(
				'INDUSTRY',
				array('type' => 'list', 'default' => true, 'partial' => true)
			),
			'REVENUE' => $this->createField(
				'REVENUE',
				array('type' => 'number')
			),
			'CURRENCY_ID' => $this->createField(
				'CURRENCY_ID',
				array('type' => 'list', 'partial' => true)
			),
			'EMPLOYEES' => $this->createField(
				'EMPLOYEES',
				array('type' => 'list', 'default' => true, 'partial' => true)
			),
			'COMMENTS' => $this->createField('COMMENTS')
		);

		if($this->settings->checkFlag(CompanySettings::FLAG_ENABLE_ADDRESS))
		{
			$addressLabels = EntityAddress::getShortLabels();
			$result += array(
				'ADDRESS' => $this->createField(
					'ADDRESS',
					array('name' => $addressLabels['ADDRESS'])
				),
				'ADDRESS_2' => $this->createField(
					'ADDRESS_2',
					array('name' => $addressLabels['ADDRESS_2'])
				),
				'ADDRESS_CITY' => $this->createField(
					'ADDRESS_CITY',
					array('name' => $addressLabels['CITY'])
				),
				'ADDRESS_REGION' => $this->createField(
					'ADDRESS_REGION',
					array('name' => $addressLabels['REGION'])
				),
				'ADDRESS_PROVINCE' => $this->createField(
					'ADDRESS_PROVINCE',
					array('name' => $addressLabels['PROVINCE'])
				),
				'ADDRESS_POSTAL_CODE' => $this->createField(
					'ADDRESS_POSTAL_CODE',
					array('name' => $addressLabels['POSTAL_CODE'])
				),
				'ADDRESS_COUNTRY' => $this->createField(
					'ADDRESS_COUNTRY',
					array('name' => $addressLabels['COUNTRY'])
				)
			);

			$regAddressLabels = EntityAddress::getShortLabels(EntityAddress::Registered);
			$result += array(
				'ADDRESS_LEGAL' => $this->createField(
					'ADDRESS_LEGAL',
					array('name' => $regAddressLabels['ADDRESS'])
				),
				'REG_ADDRESS_2' => $this->createField(
					'REG_ADDRESS_2',
					array('name' => $regAddressLabels['ADDRESS_2'])
				),
				'REG_ADDRESS_CITY' => $this->createField(
					'REG_ADDRESS_CITY',
					array('name' => $regAddressLabels['CITY'])
				),
				'REG_ADDRESS_REGION' => $this->createField(
					'REG_ADDRESS_REGION',
					array('name' => $regAddressLabels['REGION'])
				),
				'REG_ADDRESS_PROVINCE' => $this->createField(
					'REG_ADDRESS_PROVINCE',
					array('name' => $regAddressLabels['PROVINCE'])
				),
				'REG_ADDRESS_POSTAL_CODE' => $this->createField(
					'REG_ADDRESS_POSTAL_CODE',
					array('name' => $regAddressLabels['POSTAL_CODE'])
				),
				'REG_ADDRESS_COUNTRY' => $this->createField(
					'REG_ADDRESS_COUNTRY',
					array('name' => $regAddressLabels['COUNTRY'])
				)
			);
		}

		$result += array(
			'WEBFORM_ID' => $this->createField(
				'WEBFORM_ID',
				array('type' => 'list', 'partial' => true)
			),
			'ORIGINATOR_ID' => $this->createField(
				'ORIGINATOR_ID',
				array('type' => 'list', 'partial' => true)
			),
		);

		Crm\Tracking\UI\Filter::appendFields($result, $this);

		//region UTM
		foreach (Crm\UtmTable::getCodeNames() as $code => $name)
		{
			$result[$code] = $this->createField($code, array('name' => $name));
		}
		//endregion
		return $result;
	}

	/**
	 * Prepare complete field data for specified field.
	 * @param string $fieldID Field ID.
	 * @return array|null
	 */
	public function prepareFieldData($fieldID)
	{
		if($fieldID === 'COMPANY_TYPE')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmStatus::GetStatusList('COMPANY_TYPE')
			);
		}
		elseif($fieldID === 'INDUSTRY')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmStatus::GetStatusList('INDUSTRY')
			);
		}
		elseif($fieldID === 'CURRENCY_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmCurrencyHelper::PrepareListItems()
			);
		}
		elseif($fieldID === 'EMPLOYEES')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmStatus::GetStatusList('EMPLOYEES')
			);
		}
		elseif($fieldID === 'ASSIGNED_BY_ID')
		{
			return array(
				'params' => array(
					'context' => 'CRM_COMPANY_FILTER_ASSIGNED_BY_ID',
					'multiple' => 'Y',
					'contextCode' => 'U',
					'enableAll' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'prefix' => 'U'
				)
			);
		}
		elseif($fieldID === 'CREATED_BY_ID')
		{
			return array(
				'params' => array(
					'context' => 'CRM_COMPANY_FILTER_CREATED_BY_ID',
					'multiple' => 'Y',
					'contextCode' => 'U',
					'enableAll' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'prefix' => 'U'
				)
			);
		}
		elseif($fieldID === 'MODIFY_BY_ID')
		{
			return array(
				'params' => array(
					'context' => 'CRM_COMPANY_FILTER_MODIFY_BY_ID',
					'multiple' => 'Y',
					'contextCode' => 'U',
					'enableAll' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'prefix' => 'U'
				)
			);
		}
		elseif($fieldID === 'ACTIVITY_COUNTER')
		{
			return EntityCounterType::getListFilterInfo(
				array('params' => array('multiple' => 'Y')),
				array('ENTITY_TYPE_ID' => \CCrmOwnerType::Company)
			);
		}
		elseif($fieldID === 'COMMUNICATION_TYPE')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmFieldMulti::PrepareListItems(array(\CCrmFieldMulti::PHONE, \CCrmFieldMulti::EMAIL))
			);
		}
		elseif(Crm\Tracking\UI\Filter::hasField($fieldID))
		{
			return Crm\Tracking\UI\Filter::getFieldData($fieldID);
		}
		elseif($fieldID === 'WEBFORM_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => Crm\WebForm\Manager::getListNames()
			);
		}
		elseif($fieldID === 'ORIGINATOR_ID')
		{
			return array(
				'items' => array('' => Loc::getMessage('CRM_COMPANY_FILTER_ALL'))
					+ \CCrmExternalSaleHelper::PrepareListItems()
			);
		}
		return null;
	}

	/**
	 * Prepare field parameter for specified field.
	 * @param array $filter Filter params.
	 * @param string $fieldID Field ID.
	 * @return void
	 */
	public function prepareListFilterParam(array &$filter, $fieldID)
	{
		if($fieldID === 'TITLE'
			|| $fieldID ===  'BANKING_DETAILS'
			|| $fieldID ===  'COMMENTS'
		)
		{
			$value = isset($filter[$fieldID]) ? trim($filter[$fieldID]) : '';
			if($value !== '')
			{
				$filter["?{$fieldID}"] = $value;
			}
			unset($filter[$fieldID]);
		}
	}
}