<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\ParentFieldManager;
use Bitrix\Crm\UI\EntitySelector;
use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

use Bitrix\Crm;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

class LeadDataProvider extends EntityDataProvider implements FactoryOptionable
{
	use ForceUseFactoryTrait;

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
		$addressLabels = EntityAddress::getShortLabels();

		$result = [
			'ID' => $this->createField('ID'),
			'TITLE' => $this->createField(
				'TITLE',
				[
					'default' => true,
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'SOURCE_ID' => $this->createField(
				'SOURCE_ID',
				[
					'type' => 'list',
					'partial' => true
				]
			),
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
			'ASSIGNED_BY_ID' => $this->createField(
				'ASSIGNED_BY_ID',
				[
					'type' => 'entity_selector',
					'default' => true,
					'partial' => true,
				]
			),
			'OPPORTUNITY' => $this->createField(
				'OPPORTUNITY',
				[
					'type' => 'number',
					'default' => true,
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
			'STATUS_ID' => $this->createField(
				'STATUS_ID',
				[
					'type' => 'list',
					'partial' => true
				]
			),
			'STATUS_SEMANTIC_ID' => $this->createField(
				'STATUS_SEMANTIC_ID',
				[
					'type' => 'list',
					'default' => true,
					'partial' => true
				]
			),
			'STATUS_CONVERTED' => $this->createField(
				'STATUS_CONVERTED',
				[
					'type' => 'checkbox',
					'name' => Loc::getMessage('CRM_LEAD_FILTER_STATUS_PROCESSED')
				]
			),
			'CURRENCY_ID' => $this->createField(
				'CURRENCY_ID',
				[
					'type' => 'list',
					'partial' => true
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
			'IS_RETURN_CUSTOMER' => $this->createField(
				'IS_RETURN_CUSTOMER',
				[
					'type' => 'checkbox'
				]
			),
			'ACTIVITY_COUNTER' => $this->createField(
				'ACTIVITY_COUNTER',
				[
					'type' => 'list',
					'partial' => true
				]
			),
			'COMMUNICATION_TYPE' => $this->createField(
				'COMMUNICATION_TYPE',
				[
					'type' => 'list',
					'partial' => true
				]
			),
			'HAS_PHONE' => $this->createField(
				'HAS_PHONE',
				[
					'type' => 'checkbox'
				]
			),
			'PHONE' => $this->createField('PHONE'),
			'HAS_EMAIL' => $this->createField(
				'HAS_EMAIL',
				[
					'type' => 'checkbox'
				]
			),
			'EMAIL' => $this->createField('EMAIL'),
			'WEB' => $this->createField('WEB'),
			'IM' => $this->createField('IM'),
			'CONTACT_ID' => $this->createField(
				'CONTACT_ID',
				[
					'type' => 'dest_selector',
					'partial' => true
				]
			),
			'COMPANY_ID' => $this->createField(
				'COMPANY_ID',
				[
					'type' => 'dest_selector',
					'partial' => true
				]
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
			'ADDRESS' => $this->createField(
				'ADDRESS',
				[
					'name' => $addressLabels['ADDRESS'],
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'ADDRESS_2' => $this->createField(
				'ADDRESS_2',
				[
					'name' => $addressLabels['ADDRESS_2'],
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'ADDRESS_CITY' => $this->createField(
				'ADDRESS_CITY',
				[
					'name' => $addressLabels['CITY'],
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'ADDRESS_REGION' => $this->createField(
				'ADDRESS_REGION',
				[
					'name' => $addressLabels['REGION'],
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'ADDRESS_PROVINCE' => $this->createField(
				'ADDRESS_PROVINCE',
				[
					'name' => $addressLabels['PROVINCE'],
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'ADDRESS_POSTAL_CODE' => $this->createField(
				'ADDRESS_POSTAL_CODE',
				[
					'name' => $addressLabels['POSTAL_CODE'],
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'ADDRESS_COUNTRY' => $this->createField(
				'ADDRESS_COUNTRY',
				[
					'name' => $addressLabels['COUNTRY'],
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
			'PRODUCT_ROW_PRODUCT_ID' => $this->createField(
				'PRODUCT_ROW_PRODUCT_ID',
				[
					'type' => 'entity_selector',
					'partial' => true,
				]
			),
			'WEBFORM_ID' => $this->createField(
				'WEBFORM_ID',
				[
					'type' => 'entity_selector',
					'partial' => true
				]
			),
		];

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
			\CCrmOwnerType::Lead
		);
		foreach ($parentFields as $code => $parentField)
		{
			$result[$code] = $this->createField($code, $parentField);
		}

		$result['ACTIVE_TIME_PERIOD'] = $this->createField(
			'ACTIVE_TIME_PERIOD',
			[
				'type' => 'date',
				'name' => Loc::getMessage('CRM_LEAD_FILTER_ACTIVE_TIME_PERIOD'),
				'data' => [
					'additionalFilter' => [
						'isEmpty',
						'hasAnyValue',
					],
				],
			]
		);

		$result['STATUS_ID_FROM_HISTORY'] = $this->createField(
			'STATUS_ID_FROM_HISTORY',
			[
				'type' => 'list',
				'partial' => true
			]
		);

		$result['STATUS_ID_FROM_SUPPOSED_HISTORY'] = $this->createField(
			'STATUS_ID_FROM_SUPPOSED_HISTORY',
			[
				'type' => 'list',
				'partial' => true
			]
		);


		$result['STATUS_SEMANTIC_ID_FROM_HISTORY'] = $this->createField(
			'STATUS_SEMANTIC_ID_FROM_HISTORY',
			[
				'type' => 'list',
				'partial' => true
			]
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
		elseif($fieldID === 'STATUS_ID' || $fieldID === 'STATUS_ID_FROM_HISTORY' || $fieldID === 'STATUS_ID_FROM_SUPPOSED_HISTORY')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmStatus::GetStatusList('STATUS')
			);
		}
		elseif($fieldID === 'STATUS_SEMANTIC_ID' || $fieldID === 'STATUS_SEMANTIC_ID_FROM_HISTORY')
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
		elseif(in_array($fieldID, ['ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID'], true))
		{
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Lead);
			$referenceClass = ($factory ? $factory->getDataClass() : null);

			return $this->getUserEntitySelectorParams(
				EntitySelector::CONTEXT,
				[
					'fieldName' => $fieldID,
					'referenceClass' => $referenceClass,
					'isEnableAllUsers' => $fieldID === 'ASSIGNED_BY_ID',
					'isEnableOtherUsers' => $fieldID === 'ASSIGNED_BY_ID',
				]
			);
		}
		elseif($fieldID === 'CONTACT_ID')
		{
			return array(
				'alias' => 'ASSOCIATED_CONTACT_ID',
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_LEAD_FILTER_CONTACT_ID',
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
					'enableCrmContacts' => 'Y',
					'convertJson' => 'Y'
				)
			);
		}
		elseif($fieldID === 'COMPANY_ID')
		{
			return array(
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_LEAD_FILTER_COMPANY_ID',
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
		elseif($fieldID === 'PRODUCT_ROW_PRODUCT_ID')
		{
			return [
				'params' => [
					'multiple' => 'N',
					'dialogOptions' => [
						'height' => 200,
						'context' => 'catalog-products',
						'entities' => [
							Loader::includeModule('iblock')
							&& Loader::includeModule('catalog')
								? [
									'id' => 'product',
									'options' => [
										'iblockId' => \Bitrix\Crm\Product\Catalog::getDefaultId(),
										'basePriceId' => \Bitrix\Crm\Product\Price::getBaseId(),
									],
								]
								: [],
						],
					],
				],
			];
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
			return Crm\WebForm\Helper::getEntitySelectorParams(\CCrmOwnerType::Lead);
		}
		elseif($fieldID === 'ACTIVITY_COUNTER')
		{
			return EntityCounterType::getListFilterInfo(
				array('params' => array('multiple' => 'Y')),
				array('ENTITY_TYPE_ID' => \CCrmOwnerType::Lead)
			);
		}
		elseif (ParentFieldManager::isParentFieldName($fieldID))
		{
			return Container::getInstance()->getParentFieldManager()->prepareParentFieldDataForFilterProvider(
				\CCrmOwnerType::Lead,
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
