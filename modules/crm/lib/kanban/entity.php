<?php

namespace Bitrix\Crm\Kanban;

use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Automation\Starter;
use Bitrix\Crm\Component\EntityDetails\BaseComponent;
use Bitrix\Crm\Entity\EntityEditorConfigScope;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Filter;
use Bitrix\Crm\Statistics\StatisticEntryManager;
use Bitrix\Crm\Exclusion;
use Bitrix\Crm\Service;
use Bitrix\Crm\UserField\Visibility\VisibilityManager;
use Bitrix\Main\Application;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UI\Filter\FieldAdapter;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\UI\Form\EntityEditorConfiguration;
use Bitrix\Crm\Component\EntityList\ClientDataProvider\KanbanDataProvider;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Observer\Entity\ObserverTable;
use Bitrix\Crm\Service\Display\Field;

abstract class Entity
{
	protected const OPTION_CATEGORY = 'crm';
	protected const EDITOR_CONFIG_PREFIX = 'quick_editor_v6_';
	protected const EDITOR_CONFIGURATION_CATEGORY = 'crm.entity.editor';
	protected const OPTION_NAME_VIEW_FIELDS_PREFIX = 'kanban_select_more_v4_';
	protected const OPTION_NAME_EDIT_FIELDS_PREFIX = 'kanban_edit_more_v4_';
	protected const VIEW_TYPE_VIEW = 'view';
	protected const VIEW_TYPE_EDIT = 'edit';

	protected $canEditCommonSettings = false;
	protected $filter;
	protected $categoryId = 0;
	protected $itemLastId;
	protected $entityEditorConfiguration;
	protected $userFields;
	protected $loadedItems = [];
	protected $displayedFields;
	/** @var Service\Factory */
	protected $factory;

	protected $dateFormats = [
		'short' => [
			'en' => 'F j',
			'de' => 'j. F',
			'ru' => 'j F',
		],
		'full' => [
			'en' => 'F j, Y',
			'de' => 'j. F Y',
			'ru' => 'j F Y',
		],
	];

	protected static $instances = [];

	protected const PATH_MARKERS = [
		'#lead_id#',
		'#contact_id#',
		'#company_id#',
		'#deal_id#',
		'#quote_id#',
		'#invoice_id#',
	];

	/** @var $contactDataProvider KanbanDataProvider */
	protected $contactDataProvider;
	/** @var $companyDataProvider KanbanDataProvider */
	protected $companyDataProvider;

	public function __construct()
	{
		Service\Container::getInstance()->getLocalization()->loadMessages();
		$this->initFactory();
	}

	public function initFactory(): void
	{
		$this->initFactoryByEntityTypeId($this->getTypeId());
	}

	public function initFactoryByEntityTypeId(int $entityTypeId): self
	{
		$this->factory = Container::getInstance()->getFactory($entityTypeId);

		return $this;
	}

	public function setFactory(Service\Factory $factory): self
	{
		$this->factory = $factory;

		return $this;
	}

	/**
	 * Mark that current user can or not edit common settings.
	 *
	 * @param bool $canEditCommonSettings
	 * @return $this
	 */
	public function setCanEditCommonSettings(bool $canEditCommonSettings): self
	{
		$this->canEditCommonSettings = $canEditCommonSettings;

		return $this;
	}

	/**
	 * Set current category id to work with.
	 *
	 * @param int $categoryId
	 * @return $this
	 */
	public function setCategoryId(int $categoryId): Entity
	{
		$this->categoryId = $categoryId;

		return $this;
	}

	/**
	 * Get current category id.
	 *
	 * @return int
	 */
	public function getCategoryId(): int
	{
		return $this->categoryId;
	}

	/**
	 * Get string identifier of the entity.
	 *
	 * @return string
	 */
	abstract public function getTypeName(): string;

	/**
	 * Get ENTITY_ID identifier to StatusTable.
	 *
	 * @return string
	 */
	public function getStatusEntityId(): string
	{
		return $this->factory->getStagesEntityId($this->getCategoryId());
	}

	/**
	 * Get initial fields to select items
	 *
	 * @return array
	 */
	abstract public function getItemsSelectPreset(): array;

	/**
	 * Get integer identifier of the entity.
	 *
	 * @return int
	 */
	public function getTypeId(): int
	{
		return \CCrmOwnerType::ResolveID($this->getTypeName());
	}

	public function getTitle(): string
	{
		return Loc::getMessage('CRM_KANBAN_TITLE2_' . $this->getTypeName());
	}

	public function getConfigurationPlacementUrlCode(): string
	{
		return 'crm_'.mb_strtolower($this->getTypeName());
	}

	public function getGridId(): string
	{
		$gridId = new \Bitrix\Crm\Component\EntityList\GridId($this->getTypeId());
		if($this->factory->isCategoriesSupported())
		{
			return $gridId->getValueForCategory($this->getCategoryId());
		}

		return $gridId->getValue();
	}

	public function getFilterPresets(): array
	{
		return [];
	}

	/**
	 * Return true if this entity supports kanban at all.
	 *
	 * @return bool
	 */
	public function isKanbanSupported(): bool
	{
		return true;
	}

	/**
	 * Return true if this entity supports categories
	 *
	 * @return bool
	 */
	public function isCategoriesSupported(): bool
	{
		return $this->factory->isCategoriesSupported();
	}

	/**
	 * Returns true if this entity supports rest placement to kanban.
	 *
	 * @return bool
	 */
	public function isRestPlacementSupported(): bool
	{
		return false;
	}

	/**
	 * Returns true if this entity supports custom field for price.
	 *
	 * @return bool
	 */
	public function isCustomPriceFieldsSupported(): bool
	{
		return true;
	}

	/**
	 * Returns true if this entity supports a total sum in the kanban columns.
	 *
	 * @return bool
	 */
	public function isTotalPriceSupported(): bool
	{
		return true;
	}

	/**
	 * Returns true if this entity supports quick inline editor in the kanban.
	 *
	 * @return bool
	 */
	public function isInlineEditorSupported(): bool
	{
		return true;
	}

	public function isContactCenterSupported(): bool
	{
		return false;
	}

	/**
	 * Returns true if this entity has entity links in the filter.
	 *
	 * @return bool
	 */
	public function isEntitiesLinksInFilterSupported(): bool
	{
		return false;
	}

	/**
	 * Returns true if this entity has overdue field in the filter.
	 *
	 * @return bool
	 */
	public function isOverdueFilterSupported(): bool
	{
		return $this->getCloseDateFieldName() !== null;
	}

	/**
	 * Returns field name of the close date.
	 *
	 * @return string|null
	 */
	public function getCloseDateFieldName(): ?string
	{
		return null;
	}

	/**
	 * Returns true if this entity has counters field in the filter.
	 *
	 * @return bool
	 */
	public function isActivityCountersFilterSupported(): bool
	{
		return false;
	}

	/**
	 * Returns true if this entity supports recurring.
	 *
	 * @return bool
	 */
	public function isRecurringSupported(): bool
	{
		return false;
	}

	/**
	 * Returns true if this entity has own multi fields.
	 *
	 * @return bool
	 */
	public function hasOwnMultiFields(): bool
	{
		return $this->getOwnMultiFieldsClientType() !== null;
	}

	/**
	 * Returns true if this entity supports clients fields.
	 *
	 * @return bool
	 */
	public function hasClientFields(): bool
	{
		return true;
	}

	/**
	 * Returns true if this entity has counters.
	 *
	 * @return bool
	 */
	public function isActivityCountersSupported(): bool
	{
		return true;
	}

	/**
	 * Returns true if this entity supports exclusion.
	 *
	 * @return bool
	 */
	public function isExclusionSupported(): bool
	{
		return false;
	}

	/**
	 * Returns true if item of this entity should be deleted after exclusion.
	 *
	 * @return bool
	 */
	public function isDeleteAfterExclusion(): bool
	{
		return false;
	}

	/**
	 * Returns true if automation should be started after updating items of this entity.
	 *
	 * @return bool
	 */
	public function isNeedToRunAutomation(): bool
	{
		return false;
	}

	/**
	 * Returns type of this entity for multi fields
	 *
	 * @return string|null
	 */
	public function getOwnMultiFieldsClientType(): ?string
	{
		return null;
	}

	/**
	 * Returns string identifier for
	 *
	 * @return string
	 */
	public function getEditorConfigId(): string
	{
		return static::EDITOR_CONFIG_PREFIX.
			mb_strtolower($this->getTypeName()) . '_' .
			$this->categoryId;
	}

	/**
	 * Returns true if items of this entity has "OPENED" field.
	 *
	 * @return bool
	 */
	public function hasOpenedField(): bool
	{
		return true;
	}

	protected function getEntityEditorConfiguration(): EntityEditorConfiguration
	{
		if(!$this->entityEditorConfiguration)
		{
			$this->entityEditorConfiguration = new EntityEditorConfiguration(static::EDITOR_CONFIGURATION_CATEGORY);
		}

		return $this->entityEditorConfiguration;
	}

	/**
	 * Returns true if scope of current editor configuration is common.
	 *
	 * @return bool
	 */
	public function isEditorConfigScopeCommon(): bool
	{
		$scope = $this->getEntityEditorConfiguration()->getScope($this->getEditorConfigId());
		if(!$scope)
		{
			return true;
		}

		return ($scope === EntityEditorConfigScope::COMMON);
	}

	protected function getAdditionalSelectFieldsOptionName(bool $isAddCommonSuffix = false): string
	{
		return static::OPTION_NAME_VIEW_FIELDS_PREFIX.
			mb_strtolower($this->getTypeName()) . '_' . $this->categoryId .
			($isAddCommonSuffix && $this->isEditorConfigScopeCommon() ? '_common' : '');
	}

	protected function getAdditionalEditFieldsOptionName(bool $isAddCommonSuffix = false): string
	{
		return static::OPTION_NAME_EDIT_FIELDS_PREFIX.
			mb_strtolower($this->getTypeName()) . '_' . $this->categoryId .
			($isAddCommonSuffix && $this->isEditorConfigScopeCommon() ? '_common' : '');
	}

	/**
	 * Clears settings of current user additional fields for view in kanban card.
	 */
	public function removeUserAdditionalSelectFields(): void
	{
		\CUserOptions::deleteOptionsByName(static::OPTION_CATEGORY, $this->getAdditionalSelectFieldsOptionName());
	}

	/**
	 * Resets settings of additional fields for view in kanban card.
	 *
	 * @param bool $canEditCommon
	 * @return array|string[]
	 */
	public function resetAdditionalSelectFields(bool $canEditCommon): array
	{
		$fields = $this->getDefaultAdditionalSelectFields();

		$isCommon = $this->isEditorConfigScopeCommon() && $canEditCommon;

		\CUserOptions::setOption(static::OPTION_CATEGORY, $this->getAdditionalSelectFieldsOptionName($isCommon), $fields, $isCommon);

		return $fields;
	}

	protected function getAdditionalSelectFieldsFromOptions(): ?array
	{
		$isAddCommonSuffix = $this->canEditCommonSettings;

		$fields = \CUserOptions::getOption(
			static::OPTION_CATEGORY,
			$this->getAdditionalSelectFieldsOptionName($isAddCommonSuffix),
			null
		);

		if(!$isAddCommonSuffix && !$fields)
		{
			$fields = \CUserOptions::getOption(
				static::OPTION_CATEGORY,
				$this->getAdditionalSelectFieldsOptionName(true),
				null
			);
		}

		return $fields;
	}

	protected function getDefaultAdditionalSelectFields(): array
	{
		return [
			'TITLE' => '',
			'OPPORTUNITY' => '',
			'DATE_CREATE' => '',
			'CLIENT' => '',
		];
	}

	/**
	 * Returns additional fields that should be selected to view them in the kanban card.
	 *
	 * @return array|string[]
	 */
	public function getAdditionalSelectFields(): array
	{
		$fields = $this->getAdditionalSelectFieldsFromOptions();
		if(!$fields)
		{
			$fields = $this->getDefaultAdditionalSelectFields();
		}

		return $fields;
	}

	/**
	 * Save field settings
	 *
	 * @param array $fields
	 * @param string $type - type of fields, either "view" or "edit"
	 * @param bool $canEditCommon
	 * @return array
	 */
	public function saveAdditionalFields(array $fields, string $type, bool $canEditCommon): array
	{
		$name =
			($type === static::VIEW_TYPE_VIEW)
				? $this->getAdditionalSelectFieldsOptionName($canEditCommon)
				: $this->getAdditionalEditFieldsOptionName($canEditCommon);

		$fieldsKeys = $fields ? array_keys($fields) : [];
		$currentFields = \CUserOptions::GetOption(static::OPTION_CATEGORY, $name);
		$currentFieldsKeys = $currentFields ? array_keys($currentFields) : [];
		$delete = array_diff($currentFieldsKeys, $fieldsKeys);
		$add = array_diff($fieldsKeys, $currentFieldsKeys);

		\CUserOptions::setOption('crm', $name, $fields, ($canEditCommon && $this->isEditorConfigScopeCommon()));

		return [
			'delete' => array_values($delete),
			'add' => array_values($add),
		];
	}

	/**
	 * Returns fields for edit in quick form.
	 *
	 * @return array
	 */
	public function getAdditionalEditFields(): array
	{
		$fields = $this->getAdditionalEditFieldsFromOptions();
		if(!$fields)
		{
			$fields = $this->getDefaultAdditionalSelectFields();
		}

		return (array)$fields;
	}

	protected function getAdditionalEditFieldsFromOptions(): ?array
	{
		$isAddCommonSuffix = $this->canEditCommonSettings;

		$fields = \CUserOptions::getOption(
			static::OPTION_CATEGORY,
			$this->getAdditionalEditFieldsOptionName($isAddCommonSuffix),
			null
		);

		if(!$isAddCommonSuffix && !$fields)
		{
			$fields = \CUserOptions::getOption(
				static::OPTION_CATEGORY,
				$this->getAdditionalEditFieldsOptionName(true),
				null
			);
		}

		return $fields;
	}

	/**
	 * Returns custom field name for price if it is customized.
	 */
	public function getCustomPriceFieldName(): ?string
	{
		if(!$this->isCustomPriceFieldsSupported())
		{
			return null;
		}
		$slots = StatisticEntryManager::prepareSlotBingingData($this->getTypeName() .  '_SUM_STATS');
		if (is_array($slots) && isset($slots['SLOT_BINDINGS']) && is_array($slots['SLOT_BINDINGS']))
		{
			foreach ($slots['SLOT_BINDINGS'] as $slot)
			{
				if ($slot['SLOT'] === 'SUM_TOTAL')
				{
					//todo check that field goes to the particular entity
					$res = \CUserTypeEntity::getList(
						[],
						['FIELD_NAME' => $slot['FIELD']]
					);
					if ($row = $res->fetch())
					{
						return $slot['FIELD'];
					}
				}
			}
		}

		return null;
	}

	/**
	 * Returns field name in which status id is stored.
	 *
	 * @return string
	 */
	public function getStageFieldName(): string
	{
		return 'STATUS_ID';
	}

	public function getCurrency(): string
	{
		return \CCrmCurrency::GetAccountCurrencyID();
	}

	protected function getDetailComponentName(): ?string
	{
		return null;
	}

	protected function getDetailComponent(): ?\CBitrixComponent
	{
		$componentName = $this->getDetailComponentName();
		if(!$componentName)
		{
			return null;
		}

		$componentClassName = \CBitrixComponent::includeComponentClass($componentName);
		/** @var BaseComponent $component */
		$component = new $componentClassName;

		$component->initComponent($componentName);
		$component->arResult = [
			'READ_ONLY' => false,
			'PATH_TO_USER_PROFILE' => '',
		];
		$component->setEntityID(0);

		return $component;
	}

	protected function getInlineEditorConfiguration(\CBitrixComponent $component): array
	{
		/** @var \CCrmDealDetailsComponent|\CCrmLeadDetailsComponent $component */
		return $component->prepareConfiguration();
	}

	protected function prepareFieldsSections(array $configuration): array
	{
		$sections = [];
		foreach ($configuration as $configurationSection)
		{
			if (
				isset($configurationSection['elements']) &&
				is_array($configurationSection['elements'])
			)
			{
				$tmpItems = $configurationSection['elements'];
				$configurationSection['elements'] = [];
				foreach ($tmpItems as $item)
				{
					if ($item['name'] === 'OPPORTUNITY_WITH_CURRENCY')
					{
						$configurationSection['elements']['OPPORTUNITY'] = [
							'name' => 'OPPORTUNITY',
							'title' => '',
						];
					}
					$configurationSection['elements'][$item['name']] = [
						'name' => $item['name'],
						'title' => $item['title'],
					];
				}

				if (in_array($configurationSection['name'], ['main', 'additional', 'properties']))
				{
					if ($configurationSection['name'] === 'additional')
					{
						$configurationSection['elements'] = '*';
					}
					$sections[] = $configurationSection;
				}
			}
		}

		return $sections;
	}

	/**
	 * Returns parameters for inline editor in quick form.
	 *
	 * @return array|array[]
	 */
	public function getInlineEditorParameters(): array
	{
		$result = [
			'fieldsSections' => [],
			'schemeFields' => [],
		];

		$component = $this->getDetailComponent();
		if(!$component)
		{
			return $result;
		}
		$result['userFields'] = $this->getUserFields();
		$fieldInfos = $component->prepareFieldInfos();
		$configuration = $this->getInlineEditorConfiguration($component);
		$result['fieldsSections'] = $this->prepareFieldsSections($configuration);

		$availableFields = $this->getAdditionalEditFields();
		if (isset($availableFields['OPPORTUNITY']))
		{
			$availableFields['OPPORTUNITY_WITH_CURRENCY'] = '';
		}
		foreach ($fieldInfos as $field)
		{
			if (isset($availableFields[$field['name']]))
			{
				$result['schemeFields'][$field['name']] = $field;
			}
		}
		unset($result['schemeFields']['TITLE']['visibilityPolicy'], $result['schemeFields']['TITLE']['isHeading']);

		return $result;
	}

	public function getBaseFields(): array
	{
		return $this->factory->getFieldsInfo();
	}

	/**
	 * Returns user fields of this entity and their description.
	 *
	 * @return array
	 */
	public function getUserFields(): array
	{
		if(!$this->userFields)
		{
			global $USER_FIELD_MANAGER;
			$userType = new \CCrmUserType($USER_FIELD_MANAGER, \CCrmOwnerType::ResolveUserFieldEntityID($this->getTypeId()));

			$this->userFields = $userType->GetEntityFields(0);
		}

		return $this->userFields;
	}

	/**
	 * Returns additional permission parameters for the crm.kanban component.
	 *
	 * @param \CCrmPerms $permissions
	 * @return array
	 */
	public function getPermissionParameters(\CCrmPerms $permissions): array
	{
		return [];
	}

	protected function hasStageDependantRequiredFields(): bool
	{
		return false;
	}

	protected function getRequiredUserFieldNames(): array
	{
		$requiredFieldNamesForAllStages = [];
		$userFields = $this->getUserFields();
		foreach ($userFields as $row)
		{
			if ($row['MANDATORY'] === 'Y')
			{
				$requiredFieldNamesForAllStages[] = $row['FIELD_NAME'];
			}
		}

		return $requiredFieldNamesForAllStages;
	}

	/**
	 * Returns array where key is field name and value - list of stages where this field is required.
	 *
	 * @param array $stages
	 * @return array
	 */
	public function getRequiredFieldsByStages(array $stages): array
	{
		$requiredFieldNamesForAllStages = $this->getRequiredUserFieldNames();

		$requiredFields = [];
		if (FieldAttributeManager::isEnabled())
		{
			foreach($stages as $stage)
			{
				$stageRequiredFields = FieldAttributeManager::getRequiredFields(
					$this->getTypeId(),
					0,
					[
						$this->getStageFieldName() => $stage['STATUS_ID'],
					]
				);
				foreach ($stageRequiredFields as $requiredFieldsBlock)
				{
					foreach ($requiredFieldsBlock as $fieldName)
					{
						$requiredFields[$fieldName] = $requiredFields[$fieldName] ?? [];
						$requiredFields[$fieldName][] = $stage['STATUS_ID'];
					}
				}
				foreach ($requiredFieldNamesForAllStages as $fieldName)
				{
					$requiredFields[$fieldName] = $requiredFields[$fieldName] ?? [];
					$requiredFields[$fieldName][] = $stage['STATUS_ID'];
				}
			}
		}

		return $requiredFields;
	}

	protected static function getRequiredFieldsByStagesByFactory(
		Service\Factory $factory,
		array $requiredFieldNamesForAllStages,
		array $stages,
		?int $categoryId = null
	): array
	{
		$requiredFields = [];
		if (FieldAttributeManager::isEnabled())
		{
			$fieldsData = FieldAttributeManager::getList(
				$factory->getEntityTypeId(),
				FieldAttributeManager::resolveEntityScope(
					$factory->getEntityTypeId(),
					0,
					[
						'CATEGORY_ID' => $categoryId,
					]
				)
			);
			$stagesCollection = $factory->getStages($categoryId);
			foreach($stages as $stage)
			{
				$stageRequiredFields = FieldAttributeManager::processFieldsForStages(
					$fieldsData,
					$stagesCollection,
					$stage['STATUS_ID']
				);
				$stageRequiredFields = VisibilityManager::filterNotAccessibleFields(
					$factory->getEntityTypeId(),
					$stageRequiredFields
				);
				foreach ($stageRequiredFields as $fieldName)
				{
					$requiredFields[$fieldName] = $requiredFields[$fieldName] ?? [];
					$requiredFields[$fieldName][] = $stage['STATUS_ID'];
				}
				foreach ($requiredFieldNamesForAllStages as $fieldName)
				{
					$requiredFields[$fieldName] = $requiredFields[$fieldName] ?? [];
					$requiredFields[$fieldName][] = $stage['STATUS_ID'];
				}
			}
		}

		return $requiredFields;
	}

	protected function getAddItemToStagePermissionType(string $stageId, \CCrmPerms $userPermissions): ?string
	{
		return null;
	}

	/**
	 * Returns true if user with $userPermissions can add item to stage with identifier $stageId.
	 *
	 * @param string $stageId
	 * @param \CCrmPerms $userPermissions
	 * @return bool
	 */
	public function canAddItemToStage(string $stageId, \CCrmPerms $userPermissions): bool
	{
		if (!$this->isInlineEditorSupported())
		{
			return false;
		}

		return ($this->getAddItemToStagePermissionType($stageId, $userPermissions) !== BX_CRM_PERM_NONE);
	}

	protected function getDataToCalculateTotalSums(string $fieldSum, array $filter, array $runtime): array
	{
		$data = [];

		$provider = $this->getItemsProvider();
		if (class_exists($provider))
		{
			$stageFieldName = $this->getStageFieldName();
			if (method_exists($provider, 'getListEx'))
			{
				$options = [];
				$select = [$stageFieldName];
				if (mb_strpos($fieldSum, 'UF_') === 0)
				{
					$options['FIELD_OPTIONS'] = [
						'UF_FIELDS' => [
							$fieldSum => [
								'FIELD' => $fieldSum,
								'TYPE' => 'double',
							],
						],
					];
				}
				else
				{
					$select[] = $fieldSum;
				}
				$res = $provider::GetListEx(
					[],
					$filter,
					[$stageFieldName, 'SUM' => $fieldSum],
					false,
					$select,
					$options
				);
			}
			else
			{
				$res = $provider::GetList([],
					$filter,
					[$stageFieldName, 'SUM' => $fieldSum],
					false,
					[$stageFieldName, $fieldSum]
				);
			}
			while ($row = $res->fetch())
			{
				$data[] = $row;
			}
		}

		return $data;
	}

	protected function getTotalSumFieldName(): string
	{
		return 'OPPORTUNITY_ACCOUNT';
	}

	/**
	 * Fills 'count', 'total' and 'total_format' keys in $stages.
	 *
	 * @param array $filter
	 * @param array $runtime
	 * @param array $stages
	 */
	public function fillStageTotalSums(array $filter, array $runtime, array &$stages): void
	{
		$fieldName = $this->getCustomPriceFieldName();
		if(!$fieldName)
		{
			$fieldName = $this->getTotalSumFieldName();
		}
		$data = $this->getDataToCalculateTotalSums($fieldName, $filter, $runtime);

		$stageFieldName = $this->getStageFieldName();
		foreach($data as $stageSum)
		{
			if (isset($stages[$stageSum[$stageFieldName]]))
			{
				$stages[$stageSum[$stageFieldName]]['count'] = $stageSum['CNT'];
				$stages[$stageSum[$stageFieldName]]['total'] = $stageSum[$fieldName];
				$stages[$stageSum[$stageFieldName]]['total_format'] = \CCrmCurrency::MoneyToString(round($stageSum[$fieldName]), $this->getCurrency());
			}
		}
	}

	/**
	 * Returns field name for the filter of this entity by $typeName.
	 *
	 * @param string $typeName
	 * @return string
	 */
	public function getFilterFieldNameByEntityTypeName(string $typeName): string
	{
		return $typeName . '_ID';
	}

	/**
	 * Prepare field name to filter items of this entity.
	 *
	 * @param string $field
	 * @return string
	 */
	public function prepareFilterField(string $field): string
	{
		return $field;
	}

	/**
	 * Returns table alias.
	 *
	 * @return string
	 */
	public function getTableAlias(): string
	{
		return '';
	}

	/**
	 * Returns field name for assigned.
	 *
	 * @return string
	 */
	public function getAssignedByFieldName(): string
	{
		return 'ASSIGNED_BY_ID';
	}

	/**
	 * Returns full select by $additionalFields.
	 *
	 * @param array $additionalFields
	 * @return array
	 */
	public function getItemsSelect(array $additionalFields): array
	{
		$select = $this->getItemsSelectPreset();

		if(!empty($additionalFields))
		{
			$select = array_merge($select, $additionalFields);
		}

		return $select;
	}

	/**
	 * Returns result of query for items by $parameters.
	 *
	 * @param array $parameters
	 * @return \CDBResult
	 * @throws Exception
	 */
	public function getItems(array $parameters): \CDBResult
	{
		$listEntity = \Bitrix\Crm\ListEntity\Entity::getInstance($this->getTypeName());
		if (!$listEntity)
		{
			throw new Exception('Wrong entity type name');
		}
		return $listEntity->getItems($parameters);
	}

	/**
	 * Reformat some entity-specific field names to common names.
	 *
	 * @param array $item
	 * @return array
	 */
	public function prepareItemCommonFields(array $item): array
	{
		$item['FORMAT_TIME'] = $item['FORMAT_TIME'] ?? true;

		if (!isset($item['ASSIGNED_BY']))
		{
			$item['ASSIGNED_BY'] = $item['RESPONSIBLE_ID'];
		}

		$fieldSum = $this->getCustomPriceFieldName();
		if ($fieldSum && array_key_exists($fieldSum, $item))
		{
			$item['PRICE'] = $item[$fieldSum];
		}
		elseif (!empty($item['OPPORTUNITY_ACCOUNT']))
		{
			$item['PRICE'] = $item['OPPORTUNITY_ACCOUNT'];
		}
		if (!empty($item['ACCOUNT_CURRENCY_ID']))
		{
			$item['CURRENCY_ID'] = $item['ACCOUNT_CURRENCY_ID'];
		}

		$item['CONTACT_TYPE'] = ($item['CONTACT_TYPE'] ?? '');

		$currency = $this->getCurrency();
		if (empty($item['CURRENCY_ID']) || $item['CURRENCY_ID'] === $currency)
		{
			$item['PRICE'] = (float)$item['PRICE'];
			$item['PRICE_FORMATTED'] = \CCrmCurrency::MoneyToString($item['PRICE'], $currency);
		}
		else
		{
			$item['PRICE'] = \CCrmCurrency::ConvertMoney($item['PRICE'], $item['CURRENCY_ID'], $currency);
			$item['PRICE_FORMATTED'] = \CCrmCurrency::MoneyToString($item['PRICE'], $currency);
		}

		$item['OPPORTUNITY'] = \CCrmCurrency::MoneyToString(
			$item['OPPORTUNITY'],
			$item['CURRENCY_ID']
		);

		$item['OPENED'] = in_array($item['OPENED'], ['Y', '1', 1, true], true) ? 'Y' : 'N';
		$item['DATE_FORMATTED'] = $this->getFormattedDate($item['DATE'], (bool)$item['FORMAT_TIME']);

		return $item;
	}

	public function appendRelatedEntitiesValues(array $items, array $selectedFields): array
	{
		if (in_array('OBSERVER', $selectedFields))
		{
			$observers = $this->loadObserversByEntityIds(array_keys($items));
			foreach ($items as $itemId => $item)
			{
				$items[$itemId]['OBSERVER'] = $observers[$itemId] ?? [];
			}
		}

		$factory = Container::getInstance()->getFactory($this->getTypeId());
		if ($factory && $factory->isCrmTrackingEnabled() && in_array('TRACKING_SOURCE_ID', $selectedFields))
		{
			$traces = $this->loadTracesByEntityIds(array_keys($items));
			foreach ($items as $itemId => $item)
			{
				$items[$itemId]['TRACKING_SOURCE_ID'] = $traces[$itemId] ?? '';
			}
		}

		$this->appendClientData($items, $selectedFields);

		return $items;
	}

	/**
	 * Delete items of this entity with $ids.
	 *
	 * @param array $ids
	 * @param bool $isIgnore
	 * @param \CCrmPerms|null $permissions
	 * @throws Exception
	 */
	public function deleteItems(array $ids, bool $isIgnore = false, \CCrmPerms $permissions = null): void
	{
		$provider = $this->getItemsProvider();
		if (!method_exists($provider, 'delete'))
		{
			return;
		}
		$entity = new $provider();
		if (
			$this->isExclusionSupported()
			|| !Exclusion\Access::current()->canWrite()
		)
		{
			$isIgnore = false;
		}
		foreach ($ids as $id)
		{
			if ($isIgnore)
			{
				Exclusion\Manager::excludeEntity(
					$this->getTypeId(),
					$id
				);
				if ($this->isDeleteAfterExclusion())
				{
					$entity->delete($id);
				}
			}
			else
			{
				$entity->delete($id);
			}
		}
	}

	/**
	 * Returns data of item by $id.
	 *
	 * @param int $id
	 * @return array|null
	 */
	public function getItem(int $id): ?array
	{
		$provider = $this->getItemsProvider();
		$item = $provider::getById($id);

		$this->loadedItems[$id] = $item;

		return is_array($item) ? $item : null;
	}

	/**
	 * Returns true if user with $permissions can update item with $id.
	 *
	 * @param int $id
	 * @param \CCrmPerms $permissions
	 * @return bool
	 */
	public function checkUpdatePermissions(int $id, ?\CCrmPerms $permissions = null): bool
	{
		return EntityAuthorization::checkUpdatePermission($this->getTypeId(), $id, $permissions);
	}

	/**
	 * Returns true if user with $permissions can read item with $id.
	 *
	 * @param int $id
	 * @param \CCrmPerms $permissions
	 * @return bool
	 */
	public function checkReadPermissions(int $id = 0, ?\CCrmPerms $permissions = null): bool
	{
		return EntityAuthorization::checkReadPermission($this->getTypeId(), $id, $permissions);
	}

	/**
	 * Set assigned of items with $ids.
	 *
	 * @param array $ids
	 * @param int $assignedId
	 * @param \CCrmPerms $permissions
	 * @return Result
	 */
	public function setItemsAssigned(array $ids, int $assignedId, \CCrmPerms $permissions): Result
	{
		$result = new Result();

		$provider = $this->getItemsProvider();
		$entity = new $provider();
		$fieldName = $this->getAssignedByFieldName();
		foreach ($ids as $id)
		{
			if(!$this->checkUpdatePermissions($id, $permissions))
			{
				continue;
			}
			$fields = [
				$fieldName => $assignedId,
			];
			$entity->update($id, $fields);
			if (!empty($entity->LAST_ERROR))
			{
				$result->addError(new Error($entity->LAST_ERROR));
			}
			elseif($this->isNeedToRunAutomation())
			{
				$this->runAutomationOnUpdate($id, $fields);
			}
		}

		return $result;
	}

	/**
	 * Set opened field of items with $ids.
	 *
	 * @param array $ids
	 * @param bool $isOpened
	 * @return Result
	 */
	public function updateItemsOpened(array $ids, bool $isOpened): Result
	{
		$result = new Result();

		if(!$this->hasOpenedField())
		{
			return $result;
		}

		$provider = '\CCrm' . $this->getTypeName();
		$entity = new $provider();
		$fields = [
			'OPENED' => ($isOpened ? 'Y' : 'N'),
		];
		foreach($ids as $id)
		{
			$entity->update($id, $fields);
			if (!empty($entity->LAST_ERROR))
			{
				$result->addError(new Error($entity->LAST_ERROR));
			}
			elseif ($this->isNeedToRunAutomation())
			{
				$this->runAutomationOnUpdate($id, $fields);
			}
		}

		return $result;
	}

	/**
	 * Moves item with $id to stage with id $stageId.
	 *
	 * @param int $id
	 * @param string $stageId
	 * @param array $newStateParams
	 * @param array $stages
	 * @return Result
	 */
	public function updateItemStage(int $id, string $stageId, array $newStateParams, array $stages): Result
	{
		$result = new Result();

		$provider = $this->getItemsProvider();
		$entity = new $provider(false);
		$fields = [$this->getStageFieldName() => $stageId];
		$entity->Update(
			$id,
			$fields,
			true,
			true,
			[
				'REGISTER_SONET_EVENT' => true,
			]
		);
		if (!empty($entity->LAST_ERROR))
		{
			$result->addError(new Error($entity->LAST_ERROR));
		}
		elseif ($this->isNeedToRunAutomation())
		{
			$this->runAutomationOnUpdate($id, $fields);
		}

		return $result;
	}

	protected function runAutomationOnUpdate(int $id, array $fields): void
	{
		$errors = [];
		\CCrmBizProcHelper::AutoStartWorkflows(
			$this->getTypeId(),
			$id, \CCrmBizProcEventType::Edit, $errors
		);
		$starter = new Starter(
			$this->getTypeId(),
			$id
		);
		$starter->setUserIdFromCurrent()->runOnUpdate($fields, []);
	}

	/**
	 * Moves items with $ids to the $categoryId.
	 *
	 * @param array $ids
	 * @param int $categoryId
	 * @param \CCrmPerms $permissions
	 * @return Result
	 */
	public function updateItemsCategory(array $ids, int $categoryId, \CCrmPerms $permissions): Result
	{
		return new Result();
	}

	/**
	 * Returns categories to which user with $permissions has access.
	 *
	 * @param \CCrmPerms $permissions
	 * @return array
	 */
	public function getCategories(\CCrmPerms $permissions): array
	{
		return [];
	}

	/**
	 * Returns last item identifier.
	 *
	 * @return int
	 */
	public function getItemLastId(): int
	{
		if($this->itemLastId === null)
		{
			$lastId = 0;

			$provider = $this->getItemsProvider();
			$method = method_exists($provider, 'getListEx') ? 'getListEx' : 'getList';
			if (method_exists($provider, 'getTopIDs'))
			{
				$lastId = $provider::getTopIDs(1, 'DESC');
				$lastId = !empty($lastId) ? array_shift($lastId) : 0;
			}
			else if (is_callable(array($provider, $method)))
			{
				$res = $provider::$method(
					array(
						'ID' => 'DESC',
					),
					array(
						//
					),
					false,
					array(
						'nTopCount' => 1,
					),
					array(
						'ID',
					)
				);
				if ($row = $res->fetch())
				{
					$lastId = $row['ID'];
				}
			}

			$this->itemLastId = $lastId;
		}

		return (int) $this->itemLastId;
	}

	/**
	 * Returns true if there are no items on the stage.
	 *
	 * @param string $stageId
	 * @return bool
	 */
	public function isStageEmpty(string $stageId): bool
	{
		$provider = $this->getItemsProvider();
		$method = method_exists($provider, 'getListEx') ? 'getListEx' : 'getList';
		$checkFilter = [
			$this->getStageFieldName() => $stageId,
			'CHECK_PERMISSIONS' => 'N',
		];
		if($this->isCategoriesSupported())
		{
			$checkFilter['CATEGORY_ID'] = $this->getCategoryId();
		}
		$entities = $provider::$method(
			['ID' => 'DESC'],
			$checkFilter,
			false,
			[
				'nTopCount' => 1,
			],
			['ID']
		);

		return !$entities->fetch();
	}

	protected function getCurrentUserInfo(): array
	{
		return [
			'id' => CurrentUser::get()->getId(),
			'name' => CurrentUser::get()->getFormattedName(),
		];
	}

	public function getFilterOptions(): Options
	{
		return new Options($this->getGridId(), $this->getFilterPresets());
	}

	protected function getFilter(): Filter\Filter
	{
		if(!$this->filter)
		{
			$this->filter = Filter\Factory::createEntityFilter(
				Filter\Factory::createEntitySettings($this->getTypeId(), $this->getGridId())
			);
		}

		return $this->filter;
	}

	protected function getPersistentFilterFields(): array
	{
		return [
			'ASSIGNED_BY_ID', 'ACTIVITY_COUNTER', 'STAGE_ID',
		];
	}

	public function getGridFilter(): array
	{
		$result = [];

		$filter = $this->getFilter();
		$grid = $this->getFilterOptions();
		$usedFields = $grid->getUsedFields();
		foreach($this->getPersistentFilterFields() as $fieldName)
		{
			$usedFields[] = $fieldName;
		}
		foreach ($usedFields as $filterFieldID)
		{
			$filterField = $filter->getField($filterFieldID);
			if ($filterField)
			{
				$result[$filterFieldID] = $filterField->toArray();
			}
		}

		return $result;
	}

	public function getFilterLazyLoadParams(): ?array
	{
		$path = '/bitrix/components/bitrix/crm.'.mb_strtolower($this->getTypeName()) . '.list/filter.ajax.php'
			. '?filter_id=' . urlencode($this->getGridId()) . '&siteID=' . SITE_ID . '&' . bitrix_sessid_get();

		return [
			'GET_LIST' => $path . '&action=list',
			'GET_FIELD' => $path . '&action=field',
		];
	}

	/**
	 * @return \CCrmLead|\CCrmDeal|\CCrmInvoice|\CCrmQuote
	 */
	protected function getItemsProvider(): string
	{
		return '\CCrm' . $this->getTypeName();
	}

	public function getPopupFields(string $viewType): array
	{
		$result = array_merge(
			$this->getPopupGeneralFields(),
			$this->getPopupUserFields($viewType),
			$this->getPopupAdditionalFields($viewType)
		);

		if (isset($result['OPPORTUNITY']))
		{
			$result['OPPORTUNITY']['LABEL'] = Loc::getMessage('CRM_KANBAN_FIELD_OPPORTUNITY_WITH_CURRENCY');
		}

		foreach ($this->getPopupHiddenFields() as $code)
		{
			unset($result[$code]);
		}

		return $result;
	}

	protected function getPopupFieldsBeforeUserFields(): array
	{
		return [
			'OBSERVER' => [
				'ID' => 'field_OBSERVER',
				'NAME' => 'OBSERVER',
				'LABEL' => Loc::getMessage('CRM_KANBAN_FIELD_OBSERVER'),
			],
			'SOURCE_DESCRIPTION' => [
				'ID' => 'field_SOURCE_DESCRIPTION',
				'NAME' => 'SOURCE_DESCRIPTION',
				'LABEL' => Loc::getMessage('CRM_KANBAN_FIELD_SOURCE_DESCRIPTION'),
			],
		];
	}

	protected function getPopupGeneralFields(): array
	{
		$result = [];

		$fields = $this->getPopupFieldsBeforeUserFields();
		$isFieldsInserted = empty($fields);
		$filter = $this->getFilter();

		foreach ($filter->getFields() as $field)
		{
			// if this is the first user field - insert additional fields before it
			if (!$isFieldsInserted && mb_strpos($field->getId(), 'UF_') === 0)
			{
				/** @noinspection SlowArrayOperationsInLoopInspection */
				$result = array_merge(
					$result,
					$fields
				);
				$isFieldsInserted = true;
			}

			$result[$field->getId()] = FieldAdapter::adapt($field->toArray(
				['lightweight' => true]
			));
		}

		return $result;
	}

	protected function getPopupUserFields(string $viewType): array
	{
		$result = [];

		$labelCodes = [
			'LIST_COLUMN_LABEL', 'EDIT_FORM_LABEL', 'LIST_FILTER_LABEL',
		];
		foreach($this->getUserFields() as $fieldName => $userField)
		{
			if(isset($result[$fieldName]))
			{
				continue;
			}
			// detect field's label
			$fieldLabel = '';
			foreach ($labelCodes as $code)
			{
				if (isset($userField[$code]))
				{
					$fieldLabel = trim($userField[$code]);
					if ($fieldLabel)
					{
						break;
					}
				}
			}
			if (!$fieldLabel)
			{
				$fieldLabel = $fieldName;
			}
			// add to the result
			$result[$fieldName] =  [
				'ID' => 'field_' . $fieldName,
				'NAME' => $fieldName,
				'LABEL' => $fieldLabel,
			];
			if ($userField['USER_TYPE_ID'] === 'resourcebooking')
			{
				unset($result[$fieldName]);
				continue;
			}
			if (
				$viewType === static::VIEW_TYPE_EDIT &&
				$userField['USER_TYPE_ID'] === 'money'
			)
			{
				unset($result[$fieldName]);
				continue;
			}
		}

		return $result;
	}

	protected function getPopupAdditionalFields(string $viewType = self::VIEW_TYPE_VIEW): array
	{
		$fields = [
			'CLIENT' => [
				'ID' => 'CLIENT',
				'NAME' => 'CLIENT',
				'LABEL' => Loc::getMessage('CRM_COMMON_CLIENT'),
			],
		];

		if ($viewType === static::VIEW_TYPE_EDIT)
		{
			$fields['OPPORTUNITY_WITH_CURRENCY'] = [
				'ID' => 'OPPORTUNITY_WITH_CURRENCY',
				'NAME' => 'OPPORTUNITY_WITH_CURRENCY',
				'LABEL' =>  Loc::getMessage('CRM_KANBAN_FIELD_OPPORTUNITY_WITH_CURRENCY'),
			];
		}

		return $fields;
	}

	protected function getPopupHiddenFields(): array
	{
		return [
			'STAGE_ID', 'STATUS', 'STATUS_ID',
		];
	}

	public static function getInstance(string $entityTypeName): ?Entity
	{
		Loc::loadMessages(Path::combine(__DIR__, 'helper.php'));
		Loc::loadMessages(Application::getDocumentRoot() . BX_ROOT . '/components'.\CComponentEngine::makeComponentPath('bitrix:crm.kanban') . '/ajax.fields.php');
		if(!array_key_exists($entityTypeName, static::$instances))
		{
			$instance = null;
			if($entityTypeName === \CCrmOwnerType::LeadName)
			{
				$instance = ServiceLocator::getInstance()->get('crm.kanban.entity.lead');
			}
			elseif($entityTypeName === \CCrmOwnerType::DealName)
			{
				$instance = ServiceLocator::getInstance()->get('crm.kanban.entity.deal');
			}
			elseif($entityTypeName === \CCrmOwnerType::InvoiceName)
			{
				$instance = ServiceLocator::getInstance()->get('crm.kanban.entity.invoice');
			}
			elseif($entityTypeName === \CCrmOwnerType::QuoteName)
			{
				$instance = ServiceLocator::getInstance()->get('crm.kanban.entity.quote');
			}
			elseif($entityTypeName === \CCrmOwnerType::OrderName)
			{
				$instance = ServiceLocator::getInstance()->get('crm.kanban.entity.order');
			}
			elseif($entityTypeName === \CCrmOwnerType::SmartInvoiceName)
			{
				$factory = Container::getInstance()->getFactory(\CCrmOwnerType::SmartInvoice);
				$instance = ServiceLocator::getInstance()->get('crm.kanban.entity.smartInvoice');
				if ($factory)
				{
					$instance->setFactory($factory);
				}
				else
				{
					return null;
				}
			}
			else
			{
				$typeId = \CCrmOwnerType::ResolveID($entityTypeName);
				if (\CCrmOwnerType::isPossibleDynamicTypeId($typeId))
				{
					$factory = Service\Container::getInstance()->getFactory($typeId);
					if ($factory)
					{
						if (ServiceLocator::getInstance()->has('crm.kanban.entity.dynamic'))
						{
							$instance = clone ServiceLocator::getInstance()->get('crm.kanban.entity.dynamic');
						}
						else
						{
							$instance = ServiceLocator::getInstance()->get('crm.kanban.entity.dynamic');
						}
						$instance->setFactory($factory);
					}
				}
			}
			static::$instances[$entityTypeName] = $instance;
		}

		return static::$instances[$entityTypeName];
	}

	/**
	 * @param string|null $type
	 * @return array|mixed
	 */
	public function getDateFormat(?string $type)
	{
		$lang = 'ru';
		if (LANGUAGE_ID === 'de' || LANGUAGE_ID === 'en')
		{
			$lang = LANGUAGE_ID;
		}

		return ($type === null ? $this->dateFormats : $this->dateFormats[$type][$lang]);
	}

	/**
	 * @param array|null $data
	 * @param array|null $params
	 * @return array
	 */
	public function createPullItem(?array $data = null, ?array $params = null): array
	{
		$data = $this->prepareItemCommonFields($data);

		return [
			'id'=> $data['ID'],
			'data' => [
				'id' =>  $data['ID'],
				'name' => HtmlFilter::encode($data['TITLE'] ?: '#' . $data['ID']),
				'link' => $this->getUrl($data['ID']),
				'columnId' => $this->getColumnId($data),
				'price' => $data['PRICE'],
				'price_formatted' => $data['PRICE_FORMATTED'],
				'date' => $data['DATE_FORMATTED'],
				'categoryId' => $data['CATEGORY_ID'] ?? null,
			],
			'rawData' => $data // @todo get only visible values for current user
		];
	}

	public function getUrlTemplate(): string
	{
		$entityName = mb_strtoupper($this->getTypeName());
		$pathKey = 'PATH_TO_' . $entityName . '_DETAILS';
		$url = \CrmCheckPath($pathKey, '', '');
		if (
			$url === ''
			|| !\CCrmOwnerType::IsSliderEnabled($this->getTypeId())
		)
		{
			$pathKey = 'PATH_TO_' . $entityName . '_SHOW';
			$url = \CrmCheckPath($pathKey, '', '');
		}

		return $url ?? '';
	}

	/**
	 * @param int $id
	 * @return mixed
	 */
	protected function getUrl(int $id)
	{
		return str_replace(
			static::PATH_MARKERS,
			$id,
			$this->getUrlTemplate()
		);
	}

	/**
	 * @param array $data
	 * @return string
	 */
	protected function getColumnId(array $data): string
	{
		return '';
	}

	/**
	 * @param array|null $fields
	 * @param array|null $params
	 * @return array
	 */
	public function createPullStage(?array $fields = null, ?array $params = null): array
	{
		return [
			'id' => ($fields['STATUS_ID'] ?? ''),
			'sort' => ($fields['SORT'] ?? ''),
			'name' => ($fields['NAME'] ?? ''),
			'name_init' => ($fields['NAME_INIT'] ?? ''),
			'color' => ($fields['COLOR'] ?? ''),
		];
	}

	/**
	 * @param bool $clearCache  Clear static cache.
	 * @return \Bitrix\Crm\Service\Display\Field[]
	 */
	public function getDisplayedFieldsList(bool $clearCache = false): array
	{
		if (is_array($this->displayedFields) && !$clearCache)
		{
			return $this->displayedFields;
		}

		$this->displayedFields = [];

		$visibleFields = $this->getAdditionalSelectFields();
		$baseFields = $this->getBaseFields();
		$userFields = $this->getUserFields();
		$extraFields = $this->getExtraDisplayedFields();

		foreach ($visibleFields as $fieldId => $title)
		{
			if (isset($extraFields[$fieldId]) && $extraFields[$fieldId] instanceof Service\Display\Field)
			{
				$this->displayedFields[$fieldId] = $extraFields[$fieldId];
			}
			elseif (isset($baseFields[$fieldId]))
			{
				$this->displayedFields[$fieldId] = Service\Display\Field::createFromBaseField($fieldId, $baseFields[$fieldId]);
			}
			elseif (isset($userFields[$fieldId]))
			{
				$this->displayedFields[$fieldId] = Service\Display\Field::createFromUserField($fieldId, $userFields[$fieldId]);
			}
			else
			{
				$this->displayedFields[$fieldId] =
					(Service\Display\Field::createByType('string', $fieldId)) // @todo is it correct use string for all?
						->setTitle($title)
				;
			}

			if ($title !== '')
			{
				$this->displayedFields[$fieldId]->setTitle($title);
			}
			if (in_array($this->displayedFields[$fieldId]->getType(), ['date', 'datetime']))
			{
				$this->displayedFields[$fieldId]->setDisplayParams([
					'DATETIME_FORMAT' => $this->getDateFormat('full'),
				]);
			}
			if ($fieldId === $this->getAssignedByFieldName())
			{
				$this->displayedFields[$fieldId]->addDisplayParam('AS_ARRAY', true);
			}
		}

		return $this->displayedFields;
	}

	/**
	 * @return \Bitrix\Crm\Service\Display\Field[]
	 */
	protected function getExtraDisplayedFields()
	{
		$result = [];
		if ($this->hasClientFields())
		{
			$contactDataProvider = $this->getContactDataProvider();
			$companyDataProvider = $this->getCompanyDataProvider();

			$result = array_merge(
				$result,
				$contactDataProvider->getDisplayFields(),
				$companyDataProvider->getDisplayFields(),
			);
		}

		$factory = Container::getInstance()->getFactory($this->getTypeId());
		if ($factory && $factory->isObserversEnabled())
		{
			$result['OBSERVER'] =
				(Field::createByType('user', 'OBSERVER'))
					->setIsMultiple(true)
			;
		}
		if ($factory && $factory->isCrmTrackingEnabled())
		{
			$result['TRACKING_SOURCE_ID'] = Field::createByType('string', 'TRACKING_SOURCE_ID');
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public static function getPathMarkers(): array
	{
		return static::PATH_MARKERS;
	}

	/**
	 * Settings for JS of the kanban
	 *
	 * @return array
	 */
	public function getTypeInfo(): array
	{
		return [
			'disableMoveToWin' => false,
			'canShowPopupForLeadConvert' => false,
			'showTotalPrice' => $this->isTotalPriceSupported(),
			'showPersonalSetStatusNotCompletedText' => false,
			'hasPlusButtonTitle' => false,
			'useFactoryBasedApproach' => false,
			'hasRestictionToMoveToWinColumn' => false,
			'useRequiredVisibleFields' => false,
			'isQuickEditorEnabled' => $this->isInlineEditorSupported(),
			'isRecyclebinEnabled' => false,

			'canUseIgnoreItemInPanel' => false,
			'canUseCreateTaskInPanel' => false,
			'canUseCallListInPanel' => false,
			'canUseMergeInPanel' => false,

			'stageIdKey' => 'STAGE_ID',
			'defaultQuickFormFields' => ['TITLE', 'OPPORTUNITY_WITH_CURRENCY', 'CLIENT'],
			'kanbanItemClassName' => 'crm-kanban-item',
		];
	}

	public function appendClientData(array &$items, array $visibleFields): void
	{
		if (!$this->hasClientFields())
		{
			return;
		}

		$contactDataProvider = $this->getContactDataProvider();
		$contactDataProvider->addFieldsToSelect([
			'NAME',
			'LAST_NAME',
			'SECOND_NAME',
			'HONORIFIC',
		]);
		$contactDataProvider->appendResult($items, $visibleFields);

		$companyDataProvider = $this->getCompanyDataProvider();
		$companyDataProvider->addFieldsToSelect([
			'TITLE',
		]);
		$companyDataProvider->appendResult($items, $visibleFields);
	}

	public function appendMultiFieldData(array &$items, array $allowedTypes): void
	{
		if ($this->hasOwnMultiFields())
		{
			$multifieldValues = $this->loadMultiFields(
				array_keys($items),
				mb_strtoupper($this->getOwnMultiFieldsClientType()),
				$allowedTypes
			);

			$items = $this->addMultiFieldValues($items, $multifieldValues);
		}
		if ($this->hasClientFields())
		{
			$contacts = [];
			$companies = [];
			foreach ($items as $itemId => $item)
			{
				if (isset($item['contactId']) && $item['contactId'] > 0)
				{
					$contacts[$itemId] = $item['contactId'];
				}
				if (isset($item['companyId']) && $item['companyId'] > 0)
				{
					$companies[$itemId] = $item['companyId'];
				}
			}
			$items = $this->addClientMultiFieldValues($items, $allowedTypes, $contacts, \CCrmOwnerType::Contact);
			$items = $this->addClientMultiFieldValues($items, $allowedTypes, $companies, \CCrmOwnerType::Company);
		}
	}

	public function getClientFieldsRestrictions(): ?array
	{
		return null;
	}

	protected function getContactDataProvider()
	{
		if (!$this->contactDataProvider)
		{
			$this->contactDataProvider = new KanbanDataProvider(\CCrmOwnerType::Contact);
			$this->contactDataProvider->setGridId($this->getGridId());
		}

		return $this->contactDataProvider;
	}

	protected function getCompanyDataProvider()
	{
		if (!$this->companyDataProvider)
		{
			$this->companyDataProvider = new KanbanDataProvider(\CCrmOwnerType::Company);
			$this->companyDataProvider->setGridId($this->getGridId());
		}

		return $this->companyDataProvider;
	}

	protected function loadMultiFields(array $elementIds, string $entityTypeName, array $allowedTypes): array
	{
		$result = [];

		if (empty($elementIds) || empty($allowedTypes))
		{
			return $result;
		}

		$items = \CCrmFieldMulti::GetListEx([], [
			'=ENTITY_ID' => $entityTypeName,
			'@ELEMENT_ID' => $elementIds,
			'@TYPE_ID' => array_map( 'strtoupper', $allowedTypes),
		]);

		while ($multifield = $items->fetch())
		{
			$value = $multifield['VALUE'];
			$elementId = $multifield['ELEMENT_ID'];
			$complexId = $multifield['COMPLEX_ID'];
			$typeId = mb_strtolower($multifield['TYPE_ID']);

			if (!isset($result[$elementId]))
			{
				$result[$elementId] = [];
			}
			if (!isset($result[$elementId][$typeId]))
			{
				$result[$elementId][$typeId] = [];
			}
			$result[$elementId][$typeId][] = [
				'value' => htmlspecialcharsbx($value),
				'title' => \CCrmFieldMulti::GetEntityNameByComplex($complexId, false),
			];
		}

		return $result;
	}

	protected function addMultiFieldValues(array $items, array $multifieldValues, int $entityTypeId = null): array
	{
		$isOpenLinesInstalled = \Bitrix\Main\ModuleManager::isModuleInstalled('imopenlines');
		$clientType = $entityTypeId ? mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId)) : '';

		foreach ($items as $itemId => $item)
		{
			$itemMultifieldValues = $multifieldValues[$itemId] ?? [];
			foreach ($itemMultifieldValues as $code => $values)
			{
				if ($code === 'im') // we need only chat for im
				{
					if ($isOpenLinesInstalled)
					{
						foreach ($values as $val)
						{
							$val = $val['value'];
							if ((mb_strpos($val, 'imol|') === 0))
							{
								$item[$code] = $val;
								break;
							}
						}
					}
				}
				elseif($clientType)
				{
					$item[$code][$clientType] = $values;
				}
				else
				{
					$item[$code] = $values;
				}
				$item['required_fm'][mb_strtoupper($code)] = false;
			}

			$items[$itemId] = $item;
		}

		return $items;
	}

	protected function addClientMultiFieldValues(
		array $items,
		array $allowedTypes,
		array $clientIds,
		int $clientEntityTypeId
	): array
	{
		$clientsMultiFields = $this->loadMultiFields(
			array_values($clientIds),
			\CCrmOwnerType::ResolveName($clientEntityTypeId),
			$allowedTypes
		);
		$adaptedMultiFields = [];
		foreach ($clientIds as $itemId => $contactId)
		{
			$adaptedMultiFields[$itemId] = $clientsMultiFields[$contactId] ?? [];
		}

		return $this->addMultiFieldValues($items, $adaptedMultiFields, $clientEntityTypeId);
	}

	protected function loadObserversByEntityIds(array $entityIds): array
	{
		if (empty($entityIds))
		{
			return [];
		}
		$items = ObserverTable::getList([
			'select' => [
				'USER_ID', 'ENTITY_ID'
			],
			'filter' => [
				'=ENTITY_TYPE_ID' => $this->getTypeId(),
				'=ENTITY_ID' => $entityIds,
			],
			'order' => [
				'SORT' => 'ASC'
			]
		]);
		$observers = [];
		while ($item = $items->fetch())
		{
			if (!isset($observers[$item['ENTITY_ID']]))
			{
				$observers[$item['ENTITY_ID']] = [];
			}
			$observers[$item['ENTITY_ID']][] = $item['USER_ID'];
		}

		return $observers;
	}

	protected function loadTracesByEntityIds(array $entityIds): array
	{
		if (empty($entityIds))
		{
			return [];
		}

		$traces = [];
		$traceEntities = [];

		$actualSources = \Bitrix\Crm\Tracking\Provider::getActualSources();
		$actualSources = array_combine(
			array_column($actualSources, 'ID'),
			array_values($actualSources)
		);

		$channelNames = \Bitrix\Crm\Tracking\Channel\Factory::getNames();

		// get traces by entity
		$res = \Bitrix\Crm\Tracking\Internals\TraceEntityTable::getList([
			'select' => [
				'ENTITY_ID', 'TRACE_ID',
			],
			'filter' => [
				'ENTITY_TYPE_ID' => $this->getTypeId(),
				'ENTITY_ID' => $entityIds,
			],
		]);
		while ($row = $res->fetch())
		{
			$traces[$row['ENTITY_ID']] = $row;
			$traceEntities[$row['TRACE_ID']] = [];
		}

		if (!$traceEntities)
		{
			return [];
		}

		// fill paths for traces
		$res = \Bitrix\Crm\Tracking\Internals\TraceTable::getList([
			'select' => [
				'ID', 'SOURCE_ID',
			],
			'filter' => [
				'=ID' => array_keys($traceEntities),
			],
		]);
		while ($row = $res->fetch())
		{
			if (
				$row['SOURCE_ID'] &&
				isset($actualSources[$row['SOURCE_ID']])
			)
			{
				$source = $actualSources[$row['SOURCE_ID']];
				$traceEntities[$row['ID']] = [
					'NAME' => $source['NAME'],
					'DESC' => $source['DESCRIPTION'],
					'ICON' => $source['ICON_CLASS'],
					'ICON_COLOR' => $source['ICON_COLOR'],
					'IS_SOURCE' => true,
				];
			}
		}

		// additional filling
		$res = \Bitrix\Crm\Tracking\Internals\TraceChannelTable::getList([
			'select' => [
				'TRACE_ID', 'CODE',
			],
			'filter' => [
				'TRACE_ID' => array_keys($traceEntities),
			],
		]);
		while ($row = $res->fetch())
		{
			$traceEntities[$row['TRACE_ID']] = [
				'NAME' => $channelNames[$row['CODE']] ?? \Bitrix\Crm\Tracking\Channel\Base::getNameByCode($row['CODE']),
				'DESC' => '',
				'ICON' => '',
				'ICON_COLOR' => '',
				'IS_SOURCE' => true,
			];
		}

		// fill entities by full path
		foreach ($traces as $id => $trace)
		{
			if (isset($traceEntities[$trace['TRACE_ID']]))
			{
				$traces[$id] = $traceEntities[$trace['TRACE_ID']]['NAME'];
			}
			else
			{
				unset($traces[$id]);
			}
		}

		return $traces;
	}

	protected function getFormattedDate($date, bool $formatTime): string
	{
		if ($date instanceof Date || $date instanceof \DateTime)
		{
			$timestamp = $date->getTimestamp();
		}
		elseif ($date === '')
		{
			return '';
		}
		else
		{


			$timestamp = \MakeTimeStamp($date);
		}

		$now = time() + \CTimeZone::GetOffset();
		$dateFormat = $this->getDateFormat(
			date('Y') === date('Y', $timestamp)
				? 'short'
				: 'full'
		);

		return (
			!$formatTime
			? \FormatDate($dateFormat, $timestamp, $now)
			: (
				($now - $timestamp) / 3600 > 48
				? \FormatDate($dateFormat, $timestamp, $now)
				: \FormatDate('x', $timestamp, $now)
			)
		);
	}

	/**
	 * @return array
	 */
	public function getSemanticIds(): array
	{
		return [];
	}

	public function getAllowStages(array $filter = []): array
	{
		return [];
	}
}
