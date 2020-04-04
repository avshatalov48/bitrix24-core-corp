<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

use Bitrix\Crm;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\PhaseSemantics;

Loc::loadMessages(__FILE__);

class LeadDataProvider extends EntityDataProvider
{
	/** @var LeadSettings|null */
	protected $settings = null;

	function __construct(LeadSettings $settings)
	{
		$this->settings = $settings;
	}

	/**
	 * Get Settings
	 * @return LeadSettings
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
		$name = Loc::getMessage("CRM_LEAD_FILTER_{$fieldID}");
		if($name === null)
		{
			$name = \CCrmLead::GetFieldCaption($fieldID);
		}

		return $name;
	}

	/**
	 * Prepare field list.
	 * @return Field[]
	 */
	public function prepareFields()
	{
		$addressLabels = EntityAddress::getShortLabels();

		$result =  array(
			'ID' => $this->createField('ID'),
			'TITLE' => $this->createField('TITLE'),
			'SOURCE_ID' => $this->createField(
				'SOURCE_ID',
				array('type' => 'list', 'default' => true, 'partial' => true)
			),
			'NAME' => $this->createField('NAME'),
			'SECOND_NAME' => $this->createField('SECOND_NAME'),
			'LAST_NAME' => $this->createField('LAST_NAME'),
			'BIRTHDATE' => $this->createField(
				'BIRTHDATE',
				array('type' => 'date')
			),
			'DATE_CREATE' => $this->createField(
				'DATE_CREATE',
				array('type' => 'date', 'default' => true)
			),
			'DATE_MODIFY' => $this->createField(
				'DATE_MODIFY',
				array('type' => 'date')
			),
			'STATUS_ID' => $this->createField(
				'STATUS_ID',
				array('type' => 'list', 'default' => true, 'partial' => true)
			),
			'STATUS_SEMANTIC_ID' => $this->createField(
				'STATUS_SEMANTIC_ID',
				array('type' => 'list', 'default' => true, 'partial' => true)
			),
			'STATUS_CONVERTED' => $this->createField(
				'STATUS_CONVERTED',
				array('type' => 'checkbox')
			),
			'OPPORTUNITY' => $this->createField(
				'OPPORTUNITY',
				array('type' => 'number')
			),
			'CURRENCY_ID' => $this->createField(
				'CURRENCY_ID',
				array('type' => 'list', 'partial' => true)
			),
			'ASSIGNED_BY_ID' => $this->createField(
				'ASSIGNED_BY_ID',
				array('type' => 'custom_entity', 'default' => true, 'partial' => true)
			),
			'CREATED_BY_ID' => $this->createField(
				'CREATED_BY_ID',
				array('type' => 'custom_entity', 'partial' => true)
			),
			'MODIFY_BY_ID' => $this->createField(
				'MODIFY_BY_ID',
				array('type' => 'custom_entity', 'partial' => true)
			),
			'IS_RETURN_CUSTOMER' => $this->createField(
				'IS_RETURN_CUSTOMER',
				array('type' => 'checkbox')
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
			'CONTACT_ID' => $this->createField(
				'CONTACT_ID',
				array('type' => 'custom_entity', 'partial' => true)
			),
			'COMPANY_ID' => $this->createField(
				'COMPANY_ID',
				array('type' => 'custom_entity', 'partial' => true)
			),
			'COMPANY_TITLE' => $this->createField('COMPANY_TITLE'),
			'POST' => $this->createField('POST'),
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
			),
			'COMMENTS' => $this->createField('COMMENTS'),
			'PRODUCT_ROW_PRODUCT_ID' => $this->createField(
				'PRODUCT_ROW_PRODUCT_ID',
				array('type' => 'custom_entity', 'partial' => true)
			),
			'WEBFORM_ID' => $this->createField(
				'WEBFORM_ID',
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

		$result['ACTIVE_TIME_PERIOD'] = $this->createField(
			'ACTIVE_TIME_PERIOD',
			array(
				'name' => Loc::getMessage('CRM_LEAD_FILTER_ACTIVE_TIME_PERIOD'),
				'type' => 'date'
			)
		);
		return $result;
	}

	/**
	 * Prepare complete field data for specified field.
	 * @param string $fieldID Field ID.
	 * @return array|null
	 * @throws Main\NotSupportedException
	 */
	public function prepareFieldData($fieldID)
	{
		if($fieldID === 'SOURCE_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmStatus::GetStatusList('SOURCE')
			);
		}
		elseif($fieldID === 'STATUS_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmStatus::GetStatusList('STATUS')
			);
		}
		elseif($fieldID === 'STATUS_SEMANTIC_ID')
		{
			return PhaseSemantics::getListFilterInfo(
				\CCrmOwnerType::Lead,
				array('params' => array('multiple' => 'Y'))
			);
		}
		elseif($fieldID === 'CURRENCY_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmCurrencyHelper::PrepareListItems()
			);
		}
		elseif($fieldID === 'ASSIGNED_BY_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'selector' => array(
					'TYPE' => 'user',
					'DATA' => array('ID' => 'assigned_by', 'FIELD_ID' => 'ASSIGNED_BY_ID')
				)
			);
		}
		elseif($fieldID === 'CREATED_BY_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'selector' => array(
					'TYPE' => 'user',
					'DATA' => array('ID' => 'created_by', 'FIELD_ID' => 'CREATED_BY_ID')
				)
			);
		}
		elseif($fieldID === 'MODIFY_BY_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'selector' => array(
					'TYPE' => 'user',
					'DATA' => array('ID' => 'modify_by', 'FIELD_ID' => 'MODIFY_BY_ID')
				)
			);
		}
		elseif($fieldID === 'CONTACT_ID')
		{
			return array(
				//'params' => array('multiple' => 'Y'),
				'selector' => array(
					'TYPE' => 'crm_entity',
					'DATA' => array(
						'ID' => 'contact',
						'FIELD_ID' => 'CONTACT_ID',
						'FIELD_ALIAS' => 'ASSOCIATED_CONTACT_ID',
						'ENTITY_TYPE_NAMES' => array(\CCrmOwnerType::ContactName)
						//'IS_MULTIPLE' => true
					)
				)
			);
		}
		elseif($fieldID === 'COMPANY_ID')
		{
			return array(
				'selector' => array(
					'TYPE' => 'crm_entity',
					'DATA' => array(
						'ID' => 'company',
						'FIELD_ID' => 'COMPANY_ID',
						'ENTITY_TYPE_NAMES' => array(\CCrmOwnerType::CompanyName)
					)
				)
			);
		}
		elseif($fieldID === 'PRODUCT_ROW_PRODUCT_ID')
		{
			return array(
				'params' => array('multiple' => 'N'),
				'selector' => array(
					'TYPE' => 'crm_entity',
					'DATA' => array(
						'ID' => 'product',
						'FIELD_ID' => 'PRODUCT_ROW_PRODUCT_ID',
						'ENTITY_TYPE_NAMES' => array('PRODUCT'),
						'IS_MULTIPLE' => false
					)
				)
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
		elseif($fieldID === 'ACTIVITY_COUNTER')
		{
			return EntityCounterType::getListFilterInfo(
				array('params' => array('multiple' => 'Y')),
				array('ENTITY_TYPE_ID' => \CCrmOwnerType::Lead)
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
			|| $fieldID === 'NAME'
			|| $fieldID === 'LAST_NAME'
			|| $fieldID ===  'SECOND_NAME'
			|| $fieldID ===  'POST'
			|| $fieldID ===  'COMMENTS'
			|| $fieldID === 'COMPANY_TITLE'
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