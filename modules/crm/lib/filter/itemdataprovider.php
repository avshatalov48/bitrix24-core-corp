<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Currency;
use Bitrix\Crm\Integration\Main\UISelector;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\ParentFieldManager;
use Bitrix\Crm\StatusTable;
use Bitrix\Crm\UI\EntitySelector;
use Bitrix\Crm\UtmTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\StringHelper;
use Bitrix\Main\Loader;

class ItemDataProvider extends EntityDataProvider
{
	public const DISPLAY_IN_GRID = 'grid';
	public const DISPLAY_IN_FILTER = 'filter';
	public const DISPLAY_ANYWHERE = 'anywhere';

	public const TYPE_STRING = 'string';
	public const TYPE_TEXT = 'text';
	public const TYPE_NUMBER = 'number';
	public const TYPE_USER = 'entity_selector';
	public const TYPE_DATE = 'date';
	public const TYPE_BOOLEAN = 'checkbox';
	public const TYPE_CRM_ENTITY = 'crm_entity';
	public const TYPE_ENTITY_SELECTOR = 'entity_selector';
	public const TYPE_LIST = 'list';
	public const TYPE_PARENT = 'parent';

	public const FIELD_STAGE_SEMANTIC = 'STAGE_SEMANTIC_ID';

	protected const PRESET_ENTITY_SELECTOR = 'preset_entity_selector';
	protected const PRESET_DEST_SELECTOR = 'preset_dest_selector';
	protected const PRESET_DATETIME = 'preset_datetime';
	protected const PRESET_DATE = 'preset_date';
	protected const PRESET_BOOLEAN = 'preset_boolean';
	protected const PRESET_LIST = 'preset_list';

	/** @var ItemSettings */
	protected $settings;
	/** @var Factory */
	protected $factory;
	protected $stages;

	public function __construct(ItemSettings $settings, Factory $factory)
	{
		$this->settings = $settings;
		$this->factory = $factory;

		Container::getInstance()->getLocalization()->loadMessages();
	}

	protected function getFieldsInfo(): array
	{
		static $fields;

		if (!is_null($fields))
		{
			return $fields;
		}

		// todo temporary crutch for smart invoices
		$isSmartInvoice = $this->factory instanceof Factory\SmartInvoice;
		$isSmartDocument = $this->factory instanceof Factory\SmartDocument;

		$fields = [
			Item::FIELD_NAME_ID => [
				'type' => static::TYPE_NUMBER,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => true,
				'defaultFilter' => false,
			],
			Item::FIELD_NAME_TITLE => [
				'type' => static::TYPE_STRING,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => true,
				'defaultFilter' => !$isSmartInvoice,
			],
		];
		if ($isSmartInvoice)
		{
			$fields[Item\SmartInvoice::FIELD_NAME_ACCOUNT_NUMBER] = [
				'type' => static::TYPE_STRING,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => true,
				'defaultFilter' => true,
			];
		}
		elseif ($isSmartDocument)
		{
			$fields[Item\SmartDocument::FIELD_NAME_NUMBER] = [
				'type' => static::TYPE_STRING,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => true,
				'defaultFilter' => true,
			];
		}

		if ($this->factory->isLinkWithProductsEnabled())
		{
			$fields[Item::FIELD_NAME_PRODUCTS.'.PRODUCT_ID'] = [
				'type' => static::TYPE_ENTITY_SELECTOR,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => false,
				'defaultFilter' => false,
				'filterOptionPreset' => static::PRESET_ENTITY_SELECTOR,
				'customCaption' => Loc::getMessage('CRM_COMMON_PRODUCTS'),
			];
			$fields['OPPORTUNITY_WITH_CURRENCY'] = [
				'type' => static::TYPE_STRING,
				'displayGrid' => true,
				'displayFilter' => false,
				'defaultGrid' => false,
				'defaultFilter' => false,
				'customCaption' => Loc::getMessage('CRM_FILTER_ITEMDATAPROVIDER_OPPORTUNITY_WITH_CURRENCY'),
				'sortField' => Item::FIELD_NAME_OPPORTUNITY,
			];
			$fields[Item::FIELD_NAME_OPPORTUNITY] = [
				'type' => static::TYPE_NUMBER,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => false,
				'defaultFilter' => true,
			];
			$fields[Item::FIELD_NAME_CURRENCY_ID] = [
				'type' => static::TYPE_LIST,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => false,
				'defaultFilter' => false,
				'filterOptionPreset' => static::PRESET_LIST,
			];
		}

		if ($this->factory->isClientEnabled())
		{
			$fields[Item::FIELD_NAME_COMPANY_ID] = [
				'type' => static::TYPE_CRM_ENTITY,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => false,
				'defaultFilter' => $isSmartInvoice,
				'filterOptionPreset' => static::PRESET_DEST_SELECTOR,
			];
			$fields[Item::FIELD_NAME_CONTACT_ID] = [
				'type' => static::TYPE_CRM_ENTITY,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => false,
				'defaultFilter' => $isSmartInvoice,
				'filterOptionPreset' => static::PRESET_DEST_SELECTOR,
			];
			$fields['CLIENT_INFO'] = [
				'displayGrid' => true,
				'displayFilter' => false,
				'defaultGrid' => false,
				'customCaption' => Loc::getMessage('CRM_COMMON_CLIENT'),
				'sortField' => null,
			];
			$fields['CONTACT.FULL_NAME'] = [
				'type' => static::TYPE_STRING,
				'displayGrid' => false,
				'displayFilter' => true,
				'customCaption' => Loc::getMessage('CRM_FILTER_ITEMDATAPROVIDER_CONTACTS_FULL_NAME'),
			];
			$fields['COMPANY.TITLE'] = [
				'type' => static::TYPE_STRING,
				'displayGrid' => false,
				'displayFilter' => true,
				'customCaption' => Loc::getMessage('CRM_FILTER_ITEMDATAPROVIDER_COMPANY_TITLE'),
			];
		}

		$fields[Item::FIELD_NAME_CREATED_BY] = [
			'type' => static::TYPE_USER,
			'displayGrid' => true,
			'displayFilter' => true,
			'defaultGrid' => true,
			'defaultFilter' => false,
			'filterOptionPreset' => static::PRESET_ENTITY_SELECTOR,
		];
		$fields[Item::FIELD_NAME_CREATED_TIME] = [
			'type' => static::TYPE_DATE,
			'displayGrid' => true,
			'displayFilter' => true,
			'defaultGrid' => false,
			'defaultFilter' => false,
			'filterOptionPreset' => static::PRESET_DATETIME,
		];
		$fields[Item::FIELD_NAME_UPDATED_BY] = [
			'type' => static::TYPE_USER,
			'displayGrid' => true,
			'displayFilter' => true,
			'defaultGrid' => false,
			'defaultFilter' => false,
			'filterOptionPreset' => static::PRESET_ENTITY_SELECTOR,
		];
		$fields[Item::FIELD_NAME_UPDATED_TIME] = [
			'type' => static::TYPE_DATE,
			'displayGrid' => true,
			'displayFilter' => true,
			'defaultGrid' => false,
			'defaultFilter' => false,
			'filterOptionPreset' => static::PRESET_DATETIME,
		];
		$fields[Item::FIELD_NAME_ASSIGNED] = [
			'type' => static::TYPE_USER,
			'displayGrid' => true,
			'displayFilter' => true,
			'defaultGrid' => true,
			'defaultFilter' => true,
			'filterOptionPreset' => static::PRESET_ENTITY_SELECTOR,
		];
		$fields[Item::FIELD_NAME_OPENED] = [
			'type' => static::TYPE_BOOLEAN,
			'displayGrid' => true,
			'displayFilter' => true,
			'defaultGrid' => false,
			'defaultFilter' => false,
			'filterOptionPreset' => static::PRESET_BOOLEAN,
		];
		$fields[Item::FIELD_NAME_WEBFORM_ID] = [
			'type' => static::TYPE_ENTITY_SELECTOR,
			'displayGrid' => true,
			'displayFilter' => true,
			'defaultGrid' => false,
			'defaultFilter' => false,
			'filterOptionPreset' => static::PRESET_ENTITY_SELECTOR,
		];

		if ($this->factory->isLastActivityEnabled())
		{
			$fields[Item::FIELD_NAME_LAST_ACTIVITY_TIME] = [
				'type' => static::PRESET_DATETIME,
				'displayGrid' => true,
				'displayFilter' => false,
				'defaultGrid' => false,
				'defaultFilter' => false,
				'filterOptionPreset' => static::PRESET_DATETIME,
			];
		}

		if ($this->factory->isBeginCloseDatesEnabled())
		{
			$fields[Item::FIELD_NAME_BEGIN_DATE] = [
				'type' => static::TYPE_DATE,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => $isSmartInvoice,
				'defaultFilter' => $isSmartInvoice,
				'filterOptionPreset' => static::PRESET_DATE,
			];
			$fields[Item::FIELD_NAME_CLOSE_DATE] = [
				'type' => static::TYPE_DATE,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => $isSmartInvoice,
				'defaultFilter' => $isSmartInvoice,
				'filterOptionPreset' => static::PRESET_DATE,
			];
		}

		if($this->factory->isStagesEnabled())
		{
			$fields = array_merge(
				$fields,
				[
					Item::FIELD_NAME_MOVED_BY => [
						'type' => static::TYPE_USER,
						'displayGrid' => true,
						'displayFilter' => true,
						'defaultGrid' => false,
						'defaultFilter' => false,
						'filterOptionPreset' => static::PRESET_ENTITY_SELECTOR,
					],
					Item::FIELD_NAME_MOVED_TIME => [
						'type' => static::TYPE_DATE,
						'displayGrid' => true,
						'displayFilter' => true,
						'defaultGrid' => false,
						'defaultFilter' => false,
						'filterOptionPreset' => static::PRESET_DATETIME,
					],
					static::FIELD_STAGE_SEMANTIC => [
						'customCaption' => Loc::getMessage('CRM_FILTER_ITEMDATAPROVIDER_STAGE_SEMANTIC_FILTER_NAME'),
						'type' => static::TYPE_LIST,
						'displayGrid' => false,
						'displayFilter' => true,
						'defaultGrid' => false,
						'defaultFilter' => true,
						'filterOptionPreset' => static::PRESET_LIST,
					],
				]
			);

			$fields = array_merge(
				$fields,
				[
					Item::FIELD_NAME_STAGE_ID => [
						'type' => static::TYPE_LIST,
						'displayGrid' => true,
						'displayFilter' => $this->settings->getCategoryId() > 0,
						'defaultGrid' => true,
						'defaultFilter' => false,
						'filterOptionPreset' => static::PRESET_LIST,
					],
				]
			);

			$fields[Item::FIELD_NAME_PREVIOUS_STAGE_ID] = [
				'type' => static::TYPE_LIST,
				'displayGrid' => true,
				'displayFilter' => $this->settings->getCategoryId() > 0,
				'defaultGrid' => false,
				'defaultFilter' => false,
				'filterOptionPreset' => static::PRESET_LIST,
			];
		}

		if ($this->factory->isCrmTrackingEnabled())
		{
			$utmFieldInfo = [
				'type' => static::TYPE_STRING,
				'displayGrid' => true,
				'displayFilter' => false,
				'defaultGrid' => false,
				'defaultFilter' => false,
			];
			foreach (UtmTable::getCodeNames() as $code => $codeName)
			{
				$utmFieldInfo['customCaption'] = $codeName;
				$fields[$code] = $utmFieldInfo;
			}
		}

		if ($this->factory->isMyCompanyEnabled())
		{
			$fields[Item::FIELD_NAME_MYCOMPANY_ID] = [
				'type' => self::TYPE_CRM_ENTITY,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => false,
				'defaultFilter' => false,
				'filterOptionPreset' => static::PRESET_DEST_SELECTOR,
			];
			$fields[Item::FIELD_NAME_MYCOMPANY.'.TITLE'] = [
				'type' => static::TYPE_STRING,
				'displayGrid' => false,
				'displayFilter' => false,
				'customCaption' => Loc::getMessage('CRM_FILTER_ITEMDATAPROVIDER_MYCOMPANY_TITLE'),
			];
		}

		if ($this->factory->isSourceEnabled())
		{
			$fields[Item::FIELD_NAME_SOURCE_ID] = [
				'type' => static::TYPE_LIST,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => false,
				'defaultFilter' => false,
				'filterOptionPreset' => static::PRESET_LIST,
			];
			$fields[Item::FIELD_NAME_SOURCE_DESCRIPTION] = [
				'type' => static::TYPE_TEXT,
				'displayGrid' => true,
				'displayFilter' => false,
				'defaultGrid' => false,
				'defaultFilter' => false,
			];
		}

		if ($this->factory->isCategoriesEnabled())
		{
			$fields[Item::FIELD_NAME_CATEGORY_ID] = [
				'type' => static::TYPE_STRING,
				'displayGrid' => true,
				'displayFilter' => false,
				'defaultGrid' => false,
				'defaultFilter' => false,
			];
		}

		if($this->factory->isCountersEnabled()) {
			$fields['ACTIVITY_COUNTER'] = [
				'type' => static::TYPE_LIST,
				'displayGrid' => false,
				'displayFilter' => true,
				'defaultGrid' => false,
				'defaultFilter' => false,
				'filterOptionPreset' => static::PRESET_LIST,
				'customCaption' => Loc::getMessage('CRM_FILTER_ITEMDATAPROVIDER_ACTIVITY_COUNTER')
			];
		}

		$this->addParentFieldsInfo($fields);

		return $fields;
	}

	/**
	 * @param array $fields
	 */
	protected function addParentFieldsInfo(array &$fields): void
	{
		$relationManager = Container::getInstance()->getRelationManager();
		$parentRelations = $relationManager->getParentRelations($this->getFactory()->getEntityTypeId());
		foreach ($parentRelations as $relation)
		{
			$parentEntityTypeId = $relation->getParentEntityTypeId();

			$fieldInfo = [
				'type' => static::TYPE_PARENT,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => false,
				'defaultFilter' => false,
				'sortField' => null,
			];

			$factory = Container::getInstance()->getFactory($parentEntityTypeId);
			if ($factory && \CCrmOwnerType::isPossibleDynamicTypeId($parentEntityTypeId))
			{
				$fieldInfo['customCaption'] = $factory->getEntityDescription();
			}
			elseif (
				!\CCrmOwnerType::isPossibleDynamicTypeId($parentEntityTypeId)
				&& !$relation->isPredefined()
			)
			{
				$fieldInfo['customCaption'] = \CCrmOwnerType::GetCategoryCaption($parentEntityTypeId);
			}

			if (isset($fieldInfo['customCaption']))
			{
				$fields[ParentFieldManager::getParentFieldName($parentEntityTypeId)] = $fieldInfo;
			}
		}
	}

	protected function getFieldsToDisplay(string $whereToDisplay = self::DISPLAY_ANYWHERE): array
	{
		$fields = $this->getFieldsInfo();
		if ($whereToDisplay === static::DISPLAY_IN_GRID)
		{
			$fields = $this->separateFieldsByParam($fields, 'displayGrid', true);
		}
		elseif ($whereToDisplay === static::DISPLAY_IN_FILTER)
		{
			$fields = $this->separateFieldsByParam($fields, 'displayFilter', true);
		}

		return $fields;
	}

	protected function separateFieldsByParam(array $fields, string $paramName, $paramValue): array
	{
		$separated = [];
		foreach ($fields as $fieldName => $fieldParams)
		{
			if (isset($fieldParams[$paramName]) && $fieldParams[$paramName] === $paramValue)
			{
				$separated[$fieldName] = $fieldParams;
			}
		}

		return $separated;
	}

	public function getGridColumns(): array
	{
		$columns = [];

		foreach ($this->getFieldsToDisplay(static::DISPLAY_IN_GRID) as $field => $fieldParams)
		{
			$sort = $field;
			if (array_key_exists('sortField', $fieldParams))
			{
				$sort = $fieldParams['sortField'];
			}
			$columns[] = [
				'id' => $field,
				'name' => $this->getFieldName($field),
				'default' => $fieldParams['defaultGrid'],
				'sort' => $sort,
			];
		}
		if ($this->factory->isCrmTrackingEnabled())
		{
			\Bitrix\Crm\Tracking\UI\Grid::appendColumns($columns);
		}

		return $columns;
	}

	/**
	 * @inheritDoc
	 */
	public function getSettings(): ItemSettings
	{
		return $this->settings;
	}

	protected function getEntityTypeId(): int
	{
		return $this->factory->getEntityTypeId();
	}

	protected function getFieldName($fieldID): string
	{
		$customCaption = $this->getFieldsInfo()[$fieldID]['customCaption'] ?? null;
		if (!empty($customCaption))
		{
			return $customCaption;
		}

		return $this->factory->getFieldCaption($fieldID);
	}

	/**
	 * Prepare fields configs for filter
	 *
	 * @inheritDoc
	 */
	public function prepareFields(): array
	{
		$fields = [];
		foreach ($this->getFieldsToDisplay(static::DISPLAY_IN_FILTER) as $field => $fieldParams)
		{
			$customMethod = $this->getCustomFieldOptionMethodIfExists($field);
			if (!empty($fieldParams['filterOptionPreset']))
			{
				$options = $this->prepareFieldOptionByPreset($fieldParams);
			}
			elseif ($customMethod)
			{
				$options = $this->$customMethod($fieldParams);
			}
			else
			{
				$options = [
					'type' => $fieldParams['type'],
					'default' => $fieldParams['defaultFilter'] ?? null
				];
				if ($options['type'] === 'number' || $options['type'] === 'string') {
					if ($field !== 'ID') {
						$options['data'] = [
							'additionalFilter' => [
								'isEmpty',
								'hasAnyValue',
							],
						];
					}
				}
			}

			$fields[$field] = [
				'options' => $options,
			];
		}

		$this->prepareParentFields($fields);

		$result = [];
		foreach($fields as $name => $field)
		{
			$result[$name] = $this->createField($name, (!empty($field['options']) ? $field['options'] : []));
		}

		if ($this->factory->isCrmTrackingEnabled())
		{
			\Bitrix\Crm\Tracking\UI\Filter::appendFields($result, $this);
		}

		return $result;
	}

	protected function prepareParentFields(array &$fields): void
	{
		$parentFields = Container::getInstance()->getParentFieldManager()->getParentFieldsOptionsForFilterProvider(
			$this->getFactory()->getEntityTypeId()
		);

		foreach ($parentFields as $code => $parentField)
		{
			$fields[$code] = [
				'options' => $parentField,
			];
		}
	}

	/**
	 * @return Factory
	 */
	protected function getFactory(): Factory
	{
		return $this->factory;
	}

	protected function prepareFieldOptionByPreset(array $fieldParams): ?array
	{
		if ($fieldParams['filterOptionPreset'] === static::PRESET_DEST_SELECTOR)
		{
			$result = [
				'type' => 'dest_selector',
				'default' => $fieldParams['defaultFilter'],
				'partial' => true
			];
		}
		elseif ($fieldParams['filterOptionPreset'] === static::PRESET_ENTITY_SELECTOR)
		{
			$result = [
				'type' => 'entity_selector',
				'default' => $fieldParams['defaultFilter'],
				'partial' => true
			];
		}
		elseif ($fieldParams['filterOptionPreset'] === static::PRESET_DATETIME)
		{
			$result = [
				'type' => 'date',
				'default' => $fieldParams['defaultFilter'],
				'data' => [
					'time' => true,
					'exclude' => [
						\Bitrix\Main\UI\Filter\DateType::TOMORROW,
						\Bitrix\Main\UI\Filter\DateType::NEXT_DAYS,
						\Bitrix\Main\UI\Filter\DateType::NEXT_WEEK,
						\Bitrix\Main\UI\Filter\DateType::NEXT_MONTH,
					],
					'additionalFilter' => [
						'isEmpty',
						'hasAnyValue',
					],
				],
			];
		}
		elseif ($fieldParams['filterOptionPreset'] === static::PRESET_DATE)
		{
			$result = [
				'type' => 'date',
				'default' => $fieldParams['defaultFilter'],
				'data' => [
					'time' => false,
					'additionalFilter' => [
						'isEmpty',
						'hasAnyValue',
					],
				],
			];
		}
		elseif ($fieldParams['filterOptionPreset'] === static::PRESET_BOOLEAN)
		{
			$result = [
				'type' => 'checkbox',
				'default' => $fieldParams['defaultFilter']
			];
		}
		elseif ($fieldParams['filterOptionPreset'] === static::PRESET_LIST)
		{
			$result = [
				'type' => 'list',
				'default' => $fieldParams['defaultFilter'],
				'partial' => true
			];
		}

		return $result ?? null;
	}

	protected function getCustomFieldOptionMethodIfExists(string $fieldName): ?string
	{
		$methodName = 'prepareFieldOptionFor'.StringHelper::snake2camel($fieldName);
		if (method_exists($this, $methodName))
		{
			return $methodName;
		}

		return null;
	}

	/**
	 * Prepare fields data for filter
	 * @inheritDoc
	 */
	public function prepareFieldData($fieldID): ?array
	{
		$result = null;

		if (
			in_array($fieldID, $this->getFieldNamesByType(static::TYPE_USER, static::DISPLAY_IN_FILTER))
			&& $fieldID !== Item::FIELD_NAME_PRODUCTS.'.PRODUCT_ID'
			&& $fieldID !== Item::FIELD_NAME_WEBFORM_ID
		)
		{
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($this->getEntityTypeId());

			$params = [
				'fieldName' => $fieldID,
				'entityTypeId' => $this->getEntityTypeId(),
				'module' => 'crm',
				'referenceClass' => ($factory ? $factory->getDataClass() : null),
			];

			if ($factory->isCountersEnabled() && $fieldID === Item::FIELD_NAME_ASSIGNED)
			{
				$params['isEnableAllUsers'] = true;
				$params['isEnableOtherUsers'] = true;
			}

			return $this->getUserEntitySelectorParams(
				EntitySelector::CONTEXT,
				$params
			);
		}

		if (in_array($fieldID, $this->getFieldNamesByType(static::TYPE_CRM_ENTITY, static::DISPLAY_IN_FILTER)))
		{
			$result = [
				'params' => [
					'apiVersion' => 3,
					'context' => EntitySelector::CONTEXT,
					'multiple' => 'N',
					'contextCode' => 'CRM',
					'useClientDatabase' => 'N',
					'enableAll' => 'N',
					'enableUsers' => 'N',
					'enableSonetgroups' => 'N',
					'enableDepartments' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'enableCrm' => 'Y',
				]
			];

			if ($fieldID === Item::FIELD_NAME_CONTACT_ID)
			{
				$result['params']['enableCrmContacts'] = 'Y';
				$result['params']['prefix'] = UISelector\CrmContacts::PREFIX_FULL;
			}
			elseif ($fieldID === Item::FIELD_NAME_COMPANY_ID || $fieldID === Item::FIELD_NAME_MYCOMPANY_ID)
			{
				$result['params']['enableCrmCompanies'] = 'Y';
				$result['params']['prefix'] = UISelector\CrmCompanies::PREFIX_FULL;

				if ($fieldID === Item::FIELD_NAME_MYCOMPANY_ID)
				{
					$result['params']['onlyMyCompanies'] = 'Y';
				}
			}
			elseif ($fieldID === Item::FIELD_NAME_PRODUCTS.'.PRODUCT_ID')
			{
				$result['params']['enableCrmProducts'] = 'Y';
				$result['params']['prefix'] = UISelector\CrmProducts::PREFIX_FULL;
			}
		}
		elseif (in_array($fieldID, $this->getFieldNamesByType(static::TYPE_ENTITY_SELECTOR, static::DISPLAY_IN_FILTER)))
		{
			$result = [
				'params' => [
					'multiple' => 'N',
					'dialogOptions' => [
						'height' => 200,
						'context' => EntitySelector::CONTEXT,
						'entities' => [],
					],
				],
			];
			if (
				$fieldID === Item::FIELD_NAME_PRODUCTS.'.PRODUCT_ID'
				&& Loader::includeModule('iblock')
				&& Loader::includeModule('catalog')
			)
			{
				$result['params']['dialogOptions']['context'] = 'catalog-products';
				$result['params']['dialogOptions']['entities'] = [
					[
						'id' => 'product',
						'options' => [
							'iblockId' => \Bitrix\Crm\Product\Catalog::getDefaultId(),
							'basePriceId' => \Bitrix\Crm\Product\Price::getBaseId(),
						],
					]
				];
			}
			elseif ($fieldID === Item::FIELD_NAME_WEBFORM_ID)
			{
				$result['params'] = \Bitrix\Crm\WebForm\Helper::getEntitySelectorParams(
					$this->factory->getEntityTypeId(),
					Item::FIELD_NAME_WEBFORM_ID
				)['params'];
			}
		}
		elseif ($fieldID === Item::FIELD_NAME_CURRENCY_ID)
		{
			$result = [
				'params' => ['multiple' => 'Y'],
				'items' => Currency::getCurrencyList(),
			];
		}
		elseif ($fieldID === Item::FIELD_NAME_STAGE_ID || $fieldID === Item::FIELD_NAME_PREVIOUS_STAGE_ID)
		{
			$result = [
				'params' => ['multiple' => 'Y'],
				'items' => [],
			];
			foreach($this->factory->getStages($this->settings->getCategoryId()) as $stage)
			{
				$result['items'][$stage->getStatusId()] = $stage->getName();
			}
		}
		elseif ($fieldID === static::FIELD_STAGE_SEMANTIC)
		{
			$result = [
				'params' => ['multiple' => 'Y'],
				'items' => [
					PhaseSemantics::PROCESS => Loc::getMessage('CRM_FILTER_ITEMDATAPROVIDER_STAGE_SEMANTIC_IN_WORK'),
					PhaseSemantics::SUCCESS => Loc::getMessage('CRM_FILTER_ITEMDATAPROVIDER_STAGE_SEMANTIC_SUCCESS'),
					PhaseSemantics::FAILURE => Loc::getMessage('CRM_FILTER_ITEMDATAPROVIDER_STAGE_SEMANTIC_FAIL'),
				],
			];
		}
		elseif (\Bitrix\Crm\Tracking\UI\Filter::hasField($fieldID))
		{
			$result = \Bitrix\Crm\Tracking\UI\Filter::getFieldData($fieldID);
		}
		elseif ($fieldID === Item::FIELD_NAME_SOURCE_ID)
		{
			$result = [
				'params' => ['multiple' => 'Y'],
				'items' => StatusTable::getStatusesList(StatusTable::ENTITY_ID_SOURCE),
			];
		}
		elseif (ParentFieldManager::isParentFieldName($fieldID))
		{
			$result = Container::getInstance()->getParentFieldManager()->prepareParentFieldDataForFilterProvider(
				$this->factory->getEntityTypeId(),
				$fieldID
			);
		}
		elseif($fieldID === 'ACTIVITY_COUNTER')
		{
			return EntityCounterType::getListFilterInfo(
				['params' => ['multiple' => 'Y']],
				['ENTITY_TYPE_ID' => \CCrmOwnerType::SmartInvoice]
			);
		}

		return $result;
	}

	/**
	 * Prepare ORM filter from data, received from the frontend filter
	 *
	 * @param array $filter
	 * @param array $requestFilter
	 */
	public function prepareListFilter(array &$filter, array $requestFilter): void
	{
		$listFilter = new ListFilter($this->getEntityTypeId(), $this->getFieldsToDisplay(static::DISPLAY_IN_FILTER));
		$listFilter->prepareListFilter($filter, $requestFilter);
	}

	public function getFieldNamesByType(string $type, string $whereToDisplay = self::DISPLAY_ANYWHERE): array
	{
		$fields = $this->getFieldsToDisplay($whereToDisplay);

		$separated = [];
		foreach ($fields as $fieldName => $fieldParams)
		{
			if (!empty($fieldParams['type']) && $fieldParams['type'] === $type)
			{
				$separated[] = $fieldName;
			}
		}

		return $separated;
	}

	public static function processStageSemanticFilter(array $requestFilter, array &$filter): void
	{
		Filter::applyStageSemanticFilter($filter, $requestFilter, static::FIELD_STAGE_SEMANTIC);
	}

	protected function applySettingsDependantFilter(array &$filterFields): void
	{
		$categoryId = $this->getSettings()->getCategoryId();
		if ($categoryId > 0)
		{
			$filterFields['=CATEGORY_ID'] = $categoryId;
		}
	}
}
