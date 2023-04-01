<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\ParentFieldManager;
use Bitrix\Crm\UI\EntitySelector;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Counter\EntityCounterType;

Loc::loadMessages(__FILE__);

class CompanyDataProvider extends EntityDataProvider implements FactoryOptionable
{
	use ForceUseFactoryTrait;

	/** @var CompanySettings|null */
	protected $settings = null;
	protected ?Crm\Service\Factory $factory = null;

	function __construct(CompanySettings $settings)
	{
		$this->settings = $settings;
		$this->factory = Container::getInstance()->getFactory(\CCrmOwnerType::Company);
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
		$name = Loc::getMessage('CRM_COMPANY_FILTER_' . $fieldID);
		if($name === null)
		{
			$name = \CCrmCompany::GetFieldCaption($fieldID);
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
			'DATE_CREATE' => $this->createField(
				'DATE_CREATE',
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
			'PHONE' => $this->createField(
				'PHONE',
				[
					'default' => true
				]
			),
			'HAS_EMAIL' => $this->createField(
				'HAS_EMAIL',
				[
					'type' => 'checkbox'
				]
			),
			'EMAIL' => $this->createField(
				'EMAIL',
				[
					'default' => true
				]
			),
			'WEB' => $this->createField('WEB'),
			'IM' => $this->createField('IM'),
			'COMPANY_TYPE' => $this->createField(
				'COMPANY_TYPE',
				[
					'type' => 'list',
					'partial' => true
				]
			),
			'INDUSTRY' => $this->createField(
				'INDUSTRY',
				[
					'type' => 'list',
					'partial' => true
				]
			),
			'REVENUE' => $this->createField(
				'REVENUE',
				[
					'type' => 'number',
					'data' => [
						'additionalFilter' => [
							'isEmpty',
							'hasAnyValue',
						],
					],
				]
			),
			'CURRENCY_ID' => $this->createField(
				'CURRENCY_ID',
				[
					'type' => 'list',
					'partial' => true
				]
			),
			'EMPLOYEES' => $this->createField(
				'EMPLOYEES',
				[
					'type' => 'list',
					'partial' => true
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
			'ASSIGNED_BY_ID' => $this->createField(
				'ASSIGNED_BY_ID',
				[
					'type' => 'entity_selector',
					'default' => true,
					'partial' => true,
				]
			)
		];

		if($this->settings->checkFlag(CompanySettings::FLAG_ENABLE_ADDRESS))
		{
			$addressLabels = EntityAddress::getShortLabels();
			$result += [
				'ADDRESS' => $this->createField(
					'ADDRESS',
					[
						'name' => $addressLabels['ADDRESS']
					]
				),
				'ADDRESS_2' => $this->createField(
					'ADDRESS_2',
					[
						'name' => $addressLabels['ADDRESS_2']
					]
				),
				'ADDRESS_CITY' => $this->createField(
					'ADDRESS_CITY',
					[
						'name' => $addressLabels['CITY']
					]
				),
				'ADDRESS_REGION' => $this->createField(
					'ADDRESS_REGION',
					[
						'name' => $addressLabels['REGION']
					]
				),
				'ADDRESS_PROVINCE' => $this->createField(
					'ADDRESS_PROVINCE',
					[
						'name' => $addressLabels['PROVINCE']
					]
				),
				'ADDRESS_POSTAL_CODE' => $this->createField(
					'ADDRESS_POSTAL_CODE',
					[
						'name' => $addressLabels['POSTAL_CODE']
					]
				),
				'ADDRESS_COUNTRY' => $this->createField(
					'ADDRESS_COUNTRY',
					[
						'name' => $addressLabels['COUNTRY']
					]
				)
			];

			$regAddressLabels = EntityAddress::getShortLabels(Crm\EntityAddressType::Registered);
			$result += [
				'ADDRESS_LEGAL' => $this->createField(
					'ADDRESS_LEGAL',
					[
						'name' => $regAddressLabels['ADDRESS']
					]
				),
				'REG_ADDRESS_2' => $this->createField(
					'REG_ADDRESS_2',
					[
						'name' => $regAddressLabels['ADDRESS_2']
					]
				),
				'REG_ADDRESS_CITY' => $this->createField(
					'REG_ADDRESS_CITY',
					[
						'name' => $regAddressLabels['CITY']
					]
				),
				'REG_ADDRESS_REGION' => $this->createField(
					'REG_ADDRESS_REGION',
					[
						'name' => $regAddressLabels['REGION']
					]
				),
				'REG_ADDRESS_PROVINCE' => $this->createField(
					'REG_ADDRESS_PROVINCE',
					[
						'name' => $regAddressLabels['PROVINCE']
					]
				),
				'REG_ADDRESS_POSTAL_CODE' => $this->createField(
					'REG_ADDRESS_POSTAL_CODE',
					[
						'name' => $regAddressLabels['POSTAL_CODE']
					]
				),
				'REG_ADDRESS_COUNTRY' => $this->createField(
					'REG_ADDRESS_COUNTRY',
					[
						'name' => $regAddressLabels['COUNTRY']
					]
				)
			];
		}

		$result += [
			'WEBFORM_ID' => $this->createField(
				'WEBFORM_ID',
				[
					'type' => 'entity_selector',
					'partial' => true
				]
			),
			'ORIGINATOR_ID' => $this->createField(
				'ORIGINATOR_ID',
				[
					'type' => 'list',
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

		$parentFields = Container::getInstance()
			->getParentFieldManager()
			->getParentFieldsOptionsForFilterProvider(\CCrmOwnerType::Company)
		;
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
		elseif(in_array($fieldID, ['ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID'], true))
		{
			$referenceClass = ($this->factory ? $this->factory->getDataClass() : null);

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
			return Crm\WebForm\Helper::getEntitySelectorParams(\CCrmOwnerType::Company);
		}
		elseif($fieldID === 'ORIGINATOR_ID')
		{
			return array(
				'items' => array('' => Loc::getMessage('CRM_COMPANY_FILTER_ALL'))
					+ \CCrmExternalSaleHelper::PrepareListItems()
			);
		}
		elseif (ParentFieldManager::isParentFieldName($fieldID))
		{
			return Container::getInstance()->getParentFieldManager()->prepareParentFieldDataForFilterProvider(
				\CCrmOwnerType::Company,
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

	public function prepareListFilter(array &$filter, array $requestFilter): void
	{
		$listFilter = new ListFilter($this->getEntityTypeId(), $this->prepareFields());
		$listFilter->prepareListFilter($filter, $requestFilter);
	}

	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Company;
	}
}
