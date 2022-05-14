<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\ParentFieldManager;
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
		if (!$name && ParentFieldManager::isParentFieldName($fieldID))
		{
			$parentEntityTypeId = ParentFieldManager::getEntityTypeIdFromFieldName($fieldID);
			$name = \CCrmOwnerType::GetDescription($parentEntityTypeId);
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
			'NAME' => $this->createField(
				'NAME',
				[
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'SECOND_NAME' => $this->createField(
				'SECOND_NAME',
				[
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'LAST_NAME' => $this->createField(
				'LAST_NAME',
				[
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'BIRTHDATE' => $this->createField(
				'BIRTHDATE',
				[
					'type' => 'date',
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'DATE_CREATE' => $this->createField(
				'DATE_CREATE',
				[
					'type' => 'date',
					'default' => true,
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'DATE_MODIFY' => $this->createField(
				'DATE_MODIFY',
				[
					'type' => 'date',
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'ASSIGNED_BY_ID' => $this->createField(
				'ASSIGNED_BY_ID',
				[
					'type' => 'entity_selector',
					'default' => true,
					'partial' => true,
				]
			),
			'CREATED_BY_ID' => $this->createField(
				'CREATED_BY_ID',
				[
					'type' => 'entity_selector',
					'partial' => true,
				]
			),
			'MODIFY_BY_ID' => $this->createField(
				'MODIFY_BY_ID',
				[
					'type' => 'entity_selector',
					'partial' => true,
				]
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
				array('type' => 'dest_selector', 'default' => true, 'partial' => true)
			),
			'COMPANY_TITLE' => $this->createField(
				'COMPANY_TITLE',
				[
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'POST' => $this->createField(
				'POST',
				[
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'COMMENTS' => $this->createField(
				'COMMENTS',
				[
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
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
			$result[$code] = $this->createField(
				$code,
				[
					'name' => $name,
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			);
		}
		//endregion

		$parentFields = Container::getInstance()->getParentFieldManager()->getParentFieldsOptionsForFilterProvider(
			\CCrmOwnerType::Contact
		);
		foreach ($parentFields as $code => $parentField)
		{
			$result[$code] = $this->createField($code, $parentField);
		}

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
		elseif(in_array($fieldID, ['ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID'], true))
		{
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Contact);
			$referenceClass = ($factory ? $factory->getDataClass() : null);

			return $this->getUserEntitySelectorParams(
				strtolower('crm_contact_filter_' . $fieldID),
				[
					'fieldName' => $fieldID,
					'referenceClass' => $referenceClass,
				]
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
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_CONTACT_FILTER_COMPANY_ID',
					'contextCode' => 'CRM',
					'useClientDatabase' => 'N',
					'enableAll' => 'N',
					'enableDepartments' => 'N',
					'enableUsers' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'Y',
					'enableCrm' => 'Y',
					'enableCrmCompanies' => 'Y',
					'convertJson' => 'Y'
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
		elseif (ParentFieldManager::isParentFieldName($fieldID))
		{
			return Container::getInstance()->getParentFieldManager()->prepareParentFieldDataForFilterProvider(
				\CCrmOwnerType::Contact,
				$fieldID
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

	protected function applySettingsDependantFilter(array &$filterFields): void
	{
		// filter by category should be always set
		$filterFields['@CATEGORY_ID'] = (int)$this->getSettings()->getCategoryId();
	}

	protected function getCounterExtras(): array
	{
		$result = parent::getCounterExtras();

		$categoryId = $this->getSettings()->getCategoryID();
		if (!is_null($categoryId))
		{
			$result['CATEGORY_ID'] = $categoryId;
		}

		return $result;
	}
}
