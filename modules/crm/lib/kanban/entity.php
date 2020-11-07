<?php

namespace Bitrix\Crm\Kanban;

use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Automation\Starter;
use Bitrix\Crm\Entity\EntityEditorConfigScope;
use Bitrix\Crm\Filter;
use Bitrix\Crm\Statistics\StatisticEntryManager;
use Bitrix\Crm\Exclusion;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\UI\Form\EntityEditorConfiguration;

abstract class Entity
{
	protected const OPTION_CATEGORY = 'crm';
	protected const EDITOR_CONFIG_PREFIX = 'quick_editor_v6_';
	protected const EDITOR_CONFIGURATION_CATEGORY = 'crm.entity.editor';
	protected const OPTION_NAME_VIEW_FIELDS_PREFIX = 'kanban_select_more_v4_';
	protected const OPTION_NAME_EDIT_FIELDS_PREFIX = 'kanban_edit_more_v4_';

	protected $categoryId = 0;
	protected $itemLastId;
	protected $entityEditorConfiguration;
	protected $userFields;
	protected $loadedItems = [];

	protected static $instances = [];

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
	abstract public function getStatusEntityId(): string;

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

	public function getConfigurationPlacementUrlCode(): string
	{
		return 'crm_'.mb_strtolower($this->getTypeName());
	}

	public function getGridId(): string
	{
		return 'CRM_' . $this->getTypeName() . '_LIST_V12';
	}

	public function getFilterPresets(): array
	{
		return [];
	}

	/**
	 * Return true if this entity supports categories
	 *
	 * @return bool
	 */
	public function isCategoriesSupported(): bool
	{
		return false;
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
	 * Returns true if this entity supports quick inline editor in the kanban.
	 *
	 * @return bool
	 */
	public function isInlineEditorSupported(): bool
	{
		return true;
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
		return \CUserOptions::getOption(
			static::OPTION_CATEGORY,
			$this->getAdditionalSelectFieldsOptionName(true),
			null
		);
	}

	protected function getDefaultAdditionalSelectFields(): array
	{
		return [
			'TITLE' => '',
			'OPPORTUNITY' => '',
			'DATE_CREATE' => '',
			'CLIENT' => ''
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
			($type === 'view')
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

	protected function getAdditionalEditFieldsFromOptions(): array
	{
		return (array)\CUserOptions::getOption(
			static::OPTION_CATEGORY,
			$this->getAdditionalEditFieldsOptionName(),
			[]
		);
	}

	/**
	 * Returns fields for edit in quick form.
	 *
	 * @return array
	 */
	public function getAdditionalEditFields(): array
	{
		return [];
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
		$component = new $componentClassName;

		$component->initComponent($componentName);
		$component->arResult = [
			'READ_ONLY' => false,
			'PATH_TO_USER_PROFILE' => ''
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
							'title' => ''
						];
					}
					$configurationSection['elements'][$item['name']] = [
						'name' => $item['name'],
						'title' => $item['title']
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

		$availableFields = $this->getAdditionalSelectFields();
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

	/**
	 * Returns array where key is field name and value - list of stages where this field is required.
	 *
	 * @param array $stages
	 * @return array
	 */
	public function getRequiredFieldsByStages(array $stages): array
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

		$requiredFields = [];
		if (FieldAttributeManager::isEnabled())
		{
			foreach($stages as $stage)
			{
				$statusKey = ($this->getTypeId() === \CCrmOwnerType::Deal ? 'STAGE_ID' : 'STATUS_ID');
				$stageRequiredFields = FieldAttributeManager::getRequiredFields(
					$this->getTypeId(),
					0,
					[
						$statusKey => $stage['STATUS_ID']
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
		return !(
			$this->isInlineEditorSupported()
			&& $this->getAddItemToStagePermissionType($stageId, $userPermissions) === BX_CRM_PERM_NONE
		);
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
								'TYPE' => 'double'
							]
						]
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
	 */
	public function getItems(array $parameters): \CDBResult
	{
		$provider = $this->getItemsProvider();
		$method = method_exists($provider, 'getListEx') ? 'getListEx' : 'getList';

		$options = [];
		if(isset($parameters['limit'], $parameters['offset']))
		{
			$options = [
				'QUERY_OPTIONS' => [
					'LIMIT' => $parameters['limit'],
					'OFFSET' => $parameters['offset'],
				]
			];
		}

		return $provider::$method($parameters['order'], $parameters['filter'], false, false, $parameters['select'], $options);
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

		if ($item['DATE'] instanceof Date)
		{
			$item['DATE_UNIX'] = $item['DATE']->getTimestamp();
		}
		else
		{
			$item['DATE_UNIX'] = \MakeTimeStamp($item['DATE']);
		}

		return $item;
	}

	/**
	 * Delete items of this entity with $ids.
	 *
	 * @param array $ids
	 * @param bool $isIgnore
	 * @param \CCrmPerms|null $permissions
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
	public function checkUpdatePermissions(int $id, \CCrmPerms $permissions): bool
	{
		$provider = $this->getItemsProvider();

		return $provider::CheckUpdatePermission($id, $permissions);
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
				$fieldName => $assignedId
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
						'ID' => 'DESC'
					),
					array(
						//
					),
					false,
					array(
						'nTopCount' => 1
					),
					array(
						'ID'
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
		return Filter\Factory::createEntityFilter(
			Filter\Factory::createEntitySettings($this->getTypeId(), $this->getGridId())
		);
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
			'GET_FIELD' => $path . '&action=field'
		];
	}

	/**
	 * @return \CCrmLead|\CCrmDeal|\CCrmInvoice|\CCrmQuote
	 */
	protected function getItemsProvider(): string
	{
		return '\CCrm' . $this->getTypeName();
	}

	public static function getInstance(string $entityTypeName): ?Entity
	{
		Loc::loadMessages(Path::combine(__DIR__, 'helper.php'));
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

			static::$instances[$entityTypeName] = $instance;
		}

		return static::$instances[$entityTypeName];
	}
}