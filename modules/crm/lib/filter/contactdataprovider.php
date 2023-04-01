<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\ParentFieldManager;
use Bitrix\Crm\UI\EntitySelector;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Category\EntityTypeRelationsRepository;

Loc::loadMessages(__FILE__);

class ContactDataProvider extends EntityDataProvider implements FactoryOptionable
{
	use ForceUseFactoryTrait;

	/** @var ContactSettings|null */
	protected $settings = null;
	protected ?Crm\Service\Factory $factory = null;

	function __construct(ContactSettings $settings)
	{
		$this->settings = $settings;
		$this->factory = Container::getInstance()->getFactory(\CCrmOwnerType::Contact);
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
		$name = Loc::getMessage('CRM_CONTACT_FILTER_' . $fieldID);
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
		$companyCategoryId = $this->getCompanyCategoryId();

		$result = [
			'ID' => $this->createField('ID'),
			'NAME' => $this->createField(
				'NAME',
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
					'default' => true,
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
			'SOURCE_ID' => $this->createField(
				'SOURCE_ID',
				[
					'type' => 'list',
					'partial' => true
				]
			),
			'TYPE_ID' => $this->createField(
				'TYPE_ID',
				[
					'type' => 'list',
					'partial' => true
				]
			),
			'EXPORT' => $this->createField(
				'EXPORT',
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
			'COMPANY_ID' => $this->createField(
				'COMPANY_ID',
				[
					'type' => 'entity_selector',
					'name' => $companyCategoryId
						? Loc::getMessage('CRM_CONTACT_FILTER_COMPANY_ID')
						: null,
					'partial' => true
				]
			),
			'COMPANY_TITLE' => $this->createField(
				'COMPANY_TITLE',
				[
					'name' => $companyCategoryId
						? Loc::getMessage('CRM_CONTACT_FILTER_COMPANY_TITLE')
						: null,
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
			'ASSIGNED_BY_ID' => $this->createField(
				'ASSIGNED_BY_ID',
				[
					'type' => 'entity_selector',
					'default' => true,
					'partial' => true,
				]
			),
		];

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
				[
					'type' => 'entity_selector',
					'partial' => true
				]
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
		elseif($fieldID === 'COMMUNICATION_TYPE')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => \CCrmFieldMulti::PrepareListItems(array(\CCrmFieldMulti::PHONE, \CCrmFieldMulti::EMAIL))
			);
		}
		elseif($fieldID === 'COMPANY_ID')
		{
			$companyCategoryId = $this->getCompanyCategoryId();

			return [
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'height' => 200,
						'context' => 'CRM_CONTACT_FILTER_COMPANY_ID',
						'entities' => [
							[
								'id' => 'company',
								'dynamicLoad' => true,
								'dynamicSearch' => true,
								'options' => [
									'categoryId' => $companyCategoryId ?: 0,
								],
							]
						],
						'dropdownMode' => false,
					],
				],
			];
		}
		elseif(Crm\Tracking\UI\Filter::hasField($fieldID))
		{
			return Crm\Tracking\UI\Filter::getFieldData($fieldID);
		}
		elseif($fieldID === 'WEBFORM_ID')
		{
			return Crm\WebForm\Helper::getEntitySelectorParams(\CCrmOwnerType::Contact);
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

	public function prepareListFilter(array &$filter, array $requestFilter): void
	{
		$listFilter = new ListFilter($this->getEntityTypeId(), $this->prepareFields());
		$listFilter->prepareListFilter($filter, $requestFilter);
	}

	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Contact;
	}

	/**
	 * @return int|null
	 */
	private function getCompanyCategoryId(): ?int
	{
		$categoryId = $this->settings->getCategoryId();
		if (is_null($categoryId))
		{
			return null;
		}

		if ($categoryId === 0)
		{
			return 0;
		}

		return EntityTypeRelationsRepository::getInstance()->getRelatedCategoryId(
			\CCrmOwnerType::Contact,
			\CCrmOwnerType::Company,
			$categoryId
		);
	}
}
