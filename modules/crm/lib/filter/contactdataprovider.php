<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

use Bitrix\Crm;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Counter\EntityCounterType;

Loc::loadMessages(__FILE__);

class ContactDataProvider extends EntityDataProvider
{
	/** @var ContactSettings|null */
	protected $settings = null;

	function __construct(ContactSettings $settings)
	{
		$this->settings = $settings;
	}

	/**
	 * Get Settings
	 * @return ContactSettings
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
		$name = Loc::getMessage("CRM_CONTACT_FILTER_{$fieldID}");
		if($name === null)
		{
			$name = \CCrmContact::GetFieldCaption($fieldID);
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
			'SOURCE_ID' => $this->createField(
				'SOURCE_ID',
				array('type' => 'list', 'default' => true, 'partial' => true)
			),
			'TYPE_ID' => $this->createField(
				'TYPE_ID',
				array('type' => 'list', 'default' => true, 'partial' => true)
			),
			'EXPORT' => $this->createField(
				'EXPORT',
				array('type' => 'checkbox')
			),
			'ACTIVITY_COUNTER' => $this->createField(
				'ACTIVITY_COUNTER',
				array('type' => 'list', 'default' => true, 'partial' => true)
			),
			'COMPANY_ID' => $this->createField(
				'COMPANY_ID',
				array('type' => 'custom_entity', 'default' => true, 'partial' => true)
			),
			'COMPANY_TITLE' => $this->createField('COMPANY_TITLE'),
			'POST' => $this->createField('POST'),
			'COMMENTS' => $this->createField('COMMENTS'),
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
			'IM' => $this->createField('IM')
		);

		if($this->settings->checkFlag(ContactSettings::FLAG_ENABLE_ADDRESS))
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
		if($fieldID === 'SOURCE_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmStatus::GetStatusList('SOURCE')
			);
		}
		elseif($fieldID === 'TYPE_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmStatus::GetStatusList('CONTACT_TYPE')
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
		elseif($fieldID === 'COMMUNICATION_TYPE')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmFieldMulti::PrepareListItems(array(\CCrmFieldMulti::PHONE, \CCrmFieldMulti::EMAIL))
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
				'items' => array('' => Loc::getMessage('CRM_CONTACT_FILTER_ALL'))
					+ \CCrmExternalSaleHelper::PrepareListItems()
			);
		}
		elseif($fieldID === 'ACTIVITY_COUNTER')
		{
			return EntityCounterType::getListFilterInfo(
				array('params' => array('multiple' => 'Y')),
				array('ENTITY_TYPE_ID' => \CCrmOwnerType::Contact)
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
		if($fieldID === 'NAME'
			|| $fieldID === 'LAST_NAME'
			|| $fieldID ===  'SECOND_NAME'
			|| $fieldID ===  'POST'
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