<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Currency;
use Bitrix\Crm\Integration\Main\UISelector;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Search\SearchEnvironment;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\ParentFieldManager;
use Bitrix\Crm\StatusTable;
use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Crm\UtmTable;
use Bitrix\Crm\WebForm;
use Bitrix\Main\Filter\EntityDataProvider;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;
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
	public const TYPE_USER = 'dest_selector';
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
	protected $typeNames = [];

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
				'defaultFilter' => false,
			],
			Item::FIELD_NAME_CREATED_BY => [
				'type' => static::TYPE_USER,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => true,
				'defaultFilter' => false,
				'filterOptionPreset' => static::PRESET_DEST_SELECTOR,
			],
			Item::FIELD_NAME_CREATED_TIME => [
				'type' => static::TYPE_DATE,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => true,
				'defaultFilter' => false,
				'filterOptionPreset' => static::PRESET_DATETIME,
			],
			Item::FIELD_NAME_UPDATED_BY => [
				'type' => static::TYPE_USER,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => false,
				'defaultFilter' => false,
				'filterOptionPreset' => static::PRESET_DEST_SELECTOR,
			],
			Item::FIELD_NAME_UPDATED_TIME => [
				'type' => static::TYPE_DATE,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => false,
				'defaultFilter' => false,
				'filterOptionPreset' => static::PRESET_DATETIME,
			],
			Item::FIELD_NAME_ASSIGNED => [
				'type' => static::TYPE_USER,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => true,
				'defaultFilter' => true,
				'filterOptionPreset' => static::PRESET_DEST_SELECTOR,
			],
			Item::FIELD_NAME_OPENED => [
				'type' => static::TYPE_BOOLEAN,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => false,
				'defaultFilter' => false,
				'filterOptionPreset' => static::PRESET_BOOLEAN,
			],
			Item::FIELD_NAME_WEBFORM_ID => [
				'type' => static::TYPE_LIST,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => false,
				'defaultFilter' => false,
				'filterOptionPreset' => static::PRESET_LIST,
			],
		];

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
						'filterOptionPreset' => static::PRESET_DEST_SELECTOR,
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
						'defaultFilter' => true,
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

		if ($this->factory->isBeginCloseDatesEnabled())
		{
			$fields[Item::FIELD_NAME_BEGIN_DATE] = [
				'type' => static::TYPE_DATE,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => false,
				'defaultFilter' => false,
				'filterOptionPreset' => static::PRESET_DATE,
			];
			$fields[Item::FIELD_NAME_CLOSE_DATE] = [
				'type' => static::TYPE_DATE,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => false,
				'defaultFilter' => false,
				'filterOptionPreset' => static::PRESET_DATE,
			];
		}

		if ($this->factory->isClientEnabled())
		{
			$fields[Item::FIELD_NAME_COMPANY_ID] = [
				'type' => static::TYPE_CRM_ENTITY,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => false,
				'defaultFilter' => false,
				'filterOptionPreset' => static::PRESET_DEST_SELECTOR,
			];
			$fields[Item::FIELD_NAME_CONTACT_ID] = [
				'type' => static::TYPE_CRM_ENTITY,
				'displayGrid' => true,
				'displayFilter' => true,
				'defaultGrid' => false,
				'defaultFilter' => false,
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
				'defaultFilter' => false,
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
				'filterOptionPreset' => static::PRESET_DEST_SELECTOR
			];
			$fields[Item::FIELD_NAME_MYCOMPANY.'.TITLE'] = [
				'type' => static::TYPE_STRING,
				'displayGrid' => false,
				'displayFilter' => true,
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
					'default' => $fieldParams['defaultFilter']
				];
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
		$relationManager = Container::getInstance()->getRelationManager();
		$parentRelations = $relationManager->getParentRelations($this->getFactory()->getEntityTypeId());
		foreach ($parentRelations as $relation)
		{
			if (!$relation->isPredefined())
			{
				$parentEntityTypeId = $relation->getParentEntityTypeId();

				$fields[ParentFieldManager::getParentFieldName($parentEntityTypeId)] = [
					'options' => [
						'type' => 'dest_selector',
						'default' => '',
						'partial' => 1,
						'name' => $this->getEntityDescription($parentEntityTypeId),
					],
				];
			}
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
				],
			];
		}
		elseif ($fieldParams['filterOptionPreset'] === static::PRESET_DATE)
		{
			$result = [
				'type' => 'date',
				'default' => $fieldParams['defaultFilter'],
				'data' => [
					'time' => false
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

		if (in_array($fieldID, $this->getFieldNamesByType(static::TYPE_USER, static::DISPLAY_IN_FILTER)))
		{
			$result = [
				'params' => [
					'apiVersion' => 3,
					'context' => 'CRM_TYPE_'.$this->getEntityTypeId().'_ITEM_FILTER_'.$fieldID,
					'multiple' => 'Y',
					'contextCode' => 'U',
					'useSearch' => 'Y',
					'departmentFlatEnable' => 'N',
					'enableAll' => 'N',
					'enableUsers' => 'Y',
					'enableSonetgroups' => 'N',
					'enableDepartments' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'prefix' => 'U',
				]
			];
		}
		elseif (in_array($fieldID, $this->getFieldNamesByType(static::TYPE_CRM_ENTITY, static::DISPLAY_IN_FILTER)))
		{
			$result = [
				'params' => [
					'apiVersion' => 3,
					'context' => 'CRM_TYPE_'.$this->getEntityTypeId().'_ITEM_FILTER_'.$fieldID,
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
						'context' => '',
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
		elseif ($fieldID === Item::FIELD_NAME_WEBFORM_ID)
		{
			$result = [
				'params' => ['multiple' => 'Y'],
				'items' => WebForm\Manager::getListNames()
			];
		}
		elseif (ParentFieldManager::isParentFieldName($fieldID))
		{
			$result = [
				'params' => [
					'apiVersion' => 3,
					'context' => 'CRM_TYPE_' . $this->getEntityTypeId() . '_ITEM_FILTER_' . $fieldID,
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

			$parentId = ParentFieldManager::getEntityTypeIdFromFieldName($fieldID);

			if (\CCrmOwnerType::isPossibleDynamicTypeId($parentId))
			{
				$key = UISelector\Handler::ENTITY_TYPE_CRMDYNAMICS . '_'. $parentId;
				$crmDynamicTitles = [
					$key => HtmlFilter::encode($this->getEntityDescription($parentId)),
				];
			}

			$result['params'] = array_merge_recursive(
				$result['params'],
				ElementType::getEnableEntityTypesForSelectorOptions(
					[\CCrmOwnerType::ResolveName($parentId)],
					$crmDynamicTitles ?? []
				)
			);
		}

		return $result;
	}

	/**
	 * @param int|null $entityTypeId
	 * @return string|null
	 */
	protected function getEntityDescription(?int $entityTypeId = null): ?string
	{
		if (isset($this->typeNames[$entityTypeId]))
		{
			return $this->typeNames[$entityTypeId];
		}

		$entityDescription = null;
		if ($entityTypeId === null)
		{
			$entityDescription = $this->getFactory()->getEntityDescription();
		}
		elseif(\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if($factory)
			{
				$entityDescription = $factory->getEntityDescription();
			}
		}
		else
		{
			$entityDescription = \CCrmOwnerType::GetDescription($entityTypeId);
		}

		$this->typeNames[$entityTypeId] = $entityDescription;
		return $this->typeNames[$entityTypeId];
	}

	/**
	 * Prepare ORM filter from data, received from the frontend filter
	 *
	 * @param array $filter
	 * @param array $requestFilter
	 */
	public function prepareListFilter(array &$filter, array $requestFilter): void
	{
		if (isset($requestFilter['FIND']) && !empty($requestFilter['FIND']))
		{
			$filter['SEARCH_CONTENT'] = $requestFilter['FIND'];
			SearchEnvironment::prepareSearchFilter($this->getEntityTypeId(), $filter);
		}

		if ($this->factory->isCrmTrackingEnabled())
		{
			$runtime = [];
			\Bitrix\Crm\Tracking\UI\Filter::buildOrmFilter($filter, $requestFilter, $this->getEntityTypeId(), $runtime);
		}

		foreach ($this->getFieldNamesByType(static::TYPE_NUMBER, static::DISPLAY_IN_FILTER) as $fieldName)
		{
			if (isset($requestFilter[$fieldName.'_from']) && $requestFilter[$fieldName.'_from'] > 0)
			{
				$filter['>='.$fieldName] = $requestFilter[$fieldName.'_from'];
			}
			if (isset($requestFilter[$fieldName.'_to']) && $requestFilter[$fieldName.'_to'] > 0)
			{
				$filter['<='.$fieldName] = $requestFilter[$fieldName.'_to'];
			}
			if (isset($requestFilter[$fieldName]) && $requestFilter[$fieldName] > 0)
			{
				$filter['='.$fieldName] = $requestFilter[$fieldName];
			}
		}

		foreach ($this->getFieldNamesByType(static::TYPE_STRING, static::DISPLAY_IN_FILTER) as $fieldName)
		{
			if (!empty($requestFilter[$fieldName]))
			{
				$filter['%'.$fieldName] = $requestFilter[$fieldName];
			}
		}

		foreach ($this->getFieldNamesByType(static::TYPE_USER, static::DISPLAY_IN_FILTER) as $fieldName)
		{
			if (!empty($requestFilter[$fieldName]))
			{
				$filter['='.$fieldName] = $requestFilter[$fieldName];
			}
		}

		foreach ($this->getFieldNamesByType(static::TYPE_CRM_ENTITY, static::DISPLAY_IN_FILTER) as $fieldName)
		{
			if (!empty($requestFilter[$fieldName]))
			{
				$filter['='.$fieldName] = (int)$requestFilter[$fieldName];
			}
		}

		foreach ($this->getFieldNamesByType(static::TYPE_ENTITY_SELECTOR, static::DISPLAY_IN_FILTER) as $fieldName)
		{
			if (!empty($requestFilter[$fieldName]))
			{
				$filter['='.$fieldName] = (int)$requestFilter[$fieldName];
			}
		}

		foreach ($this->getFieldNamesByType(static::TYPE_DATE, static::DISPLAY_IN_FILTER) as $fieldName)
		{
			if (!empty($requestFilter[$fieldName.'_from']))
			{
				$filter['>='.$fieldName] = $requestFilter[$fieldName.'_from'];
			}
			if (!empty($requestFilter[$fieldName.'_to']))
			{
				$filter['<='.$fieldName] = $requestFilter[$fieldName.'_to'];
			}
		}

		foreach ($this->getFieldNamesByType(static::TYPE_BOOLEAN, static::DISPLAY_IN_FILTER) as $fieldName)
		{
			if (!empty($requestFilter[$fieldName]))
			{
				$filterValue = $requestFilter[$fieldName] === 'Y';

				$filter['='.$fieldName] = $filterValue;
			}
		}

		foreach ($this->getFieldNamesByType(static::TYPE_LIST, static::DISPLAY_IN_FILTER) as $fieldName)
		{
			if (!empty($requestFilter[$fieldName]))
			{
				if($fieldName === static::FIELD_STAGE_SEMANTIC && $this->factory->isStagesEnabled())
				{
					static::processStageSemanticFilter($requestFilter, $filter);
				}
				else
				{
					$filter['='.$fieldName] = $requestFilter[$fieldName];
				}
			}
		}

		$parentFields = $this->getFieldNamesByType(
			static::TYPE_PARENT,
			static::DISPLAY_IN_FILTER
		);
		foreach ($parentFields as $fieldName)
		{
			if (!empty($requestFilter[$fieldName]))
			{
				$filter[$fieldName] = $requestFilter[$fieldName];
			}
		}
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
		if (empty($requestFilter[static::FIELD_STAGE_SEMANTIC]))
		{
			return;
		}

		$semanticFilter = [];
		if (in_array(PhaseSemantics::PROCESS, $requestFilter[static::FIELD_STAGE_SEMANTIC], true))
		{
			$semanticFilter[] = [
				'STAGE.SEMANTICS' => '',
			];
			$semanticFilter[] = [
				'STAGE.SEMANTICS' => PhaseSemantics::PROCESS,
			];
		}
		if (in_array(PhaseSemantics::SUCCESS, $requestFilter[static::FIELD_STAGE_SEMANTIC], true))
		{
			$semanticFilter[] = [
				'STAGE.SEMANTICS' => PhaseSemantics::SUCCESS,
			];
		}
		if (in_array(PhaseSemantics::FAILURE, $requestFilter[static::FIELD_STAGE_SEMANTIC], true))
		{
			$semanticFilter[] = [
				'STAGE.SEMANTICS' => PhaseSemantics::FAILURE,
			];
		}

		if (!empty($semanticFilter))
		{
			$filter[] = array_merge([
				'LOGIC' => 'OR',
			], $semanticFilter);
		}
	}
}
