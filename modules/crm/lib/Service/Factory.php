<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Conversion\EntityConversionConfig;
use Bitrix\Crm\Currency;
use Bitrix\Crm\EO_Status;
use Bitrix\Crm\EO_Status_Collection;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\EventHistory\TrackedObject;
use Bitrix\Crm\StatusTable;
use Bitrix\Crm\UI\Filter\EntityHandler;
use Bitrix\Main\Application;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\UserField;

abstract class Factory
{
	protected const STAGES_CACHE_TTL = 86400;

	protected $fieldsCollection;
	protected $categories;
	protected $stages;
	protected $stageCollections;
	protected $userType;
	protected $editorAdapter;

	/** @var StatusTable */
	protected $statusTableClassName = StatusTable::class;

	protected $itemClassName = Item::class;

	/**
	 * Returns DataManager class name for items table.
	 *
	 * @internal
	 * @return string|DataManager
	 */
	abstract public function getDataClass(): string;

	/**
	 * Returns entityTypeId for this factory.
	 *
	 * @return int
	 */
	abstract public function getEntityTypeId(): int;

	/**
	 * Returns data about item fields.
	 *
	 * @return array
	 */
	abstract protected function getFieldsSettings(): array;

	/**
	 * Returns data about item fields with titles.
	 *
	 * @return array
	 */
	public function getFieldsInfo(): array
	{
		$settings = $this->getFieldsSettings();
		foreach ($settings as $name => &$field)
		{
			$field['TITLE'] = $this->getFieldCaption($name);
		}

		return $settings;
	}

	/**
	 * Returns map of common field names that have entity-specific name for the entity
	 *
	 * @return string[] commonFieldName => entityFieldName
	 */
	public function getFieldsMap(): array
	{
		return [];
	}

	/**
	 * Replaces common Item field name (Item::FIELD_NAME_*) with entity-specific name if it's needed
	 * @see Item
	 *
	 * @param string $commonFieldName
	 *
	 * @return string
	 */
	public function getEntityFieldNameByMap(string $commonFieldName): string
	{
		return $this->getFieldsMap()[$commonFieldName] ?? $commonFieldName;
	}

	/**
	 * Replaces entity-specific name with common Item field name (Item::FIELD_NAME_*) if it's needed
	 * @see Item
	 *
	 * @param string $entityFieldName
	 *
	 * @return string
	 */
	public function getCommonFieldNameByMap(string $entityFieldName): string
	{
		return array_flip($this->getFieldsMap())[$entityFieldName] ?? $entityFieldName;
	}

	final public function getItemByEntityObject(EntityObject $object): Item
	{
		$disabledFieldNames = [];

		/** @see Item::isCategoriesSupported() */
		if (!$this->isCategoriesSupported())
		{
			$disabledFieldNames[] = Item::FIELD_NAME_CATEGORY_ID;
		}

		/** @see Item::isStagesEnabled() */
		if (!$this->isStagesEnabled())
		{
			$disabledFieldNames[] = Item::FIELD_NAME_STAGE_ID;
		}

		return new $this->itemClassName(
			$this->getEntityTypeId(),
			$object,
			$this->getFieldsMap(),
			$disabledFieldNames
		);
	}

	/**
	 * Returns language-specific title of $commonFieldName. If not title is not found, returns $commonFieldName
	 *
	 * @param string $commonFieldName
	 *
	 * @return string
	 */
	public function getFieldCaption(string $commonFieldName): string
	{
		$titles = $this->getFieldTitlesMap();
		if (isset($titles[$commonFieldName]))
		{
			return $titles[$commonFieldName];
		}

		if (!$this->isFieldExists($commonFieldName))
		{
			return $commonFieldName;
		}

		$entityFieldName = $this->getEntityFieldNameByMap($commonFieldName);

		if ($this->getDataClass()::getEntity()->hasField($entityFieldName))
		{
			$caption = (string)$this->getDataClass()::getEntity()->getField($entityFieldName)->getTitle();
		}

		if ( (!empty($caption)) && ($caption !== $entityFieldName) )
		{
			return $caption;
		}

		return $commonFieldName;
	}

	protected function getFieldTitlesMap(): array
	{
		return [
			EditorAdapter::FIELD_CLIENT => Loc::getMessage('CRM_COMMON_CLIENT'),
			EditorAdapter::FIELD_PRODUCT_ROW_SUMMARY => Loc::getMessage('CRM_COMMON_PRODUCTS'),
			EditorAdapter::FIELD_OPPORTUNITY => Loc::getMessage('CRM_TYPE_ITEM_FIELD_NAME_OPPORTUNITY_WITH_CURRENCY'),
			EditorAdapter::FIELD_UTM => Loc::getMessage('CRM_COMMON_UTM'),
		];
	}

	public function isFieldExists(string $commonFieldName): bool
	{
		if ($commonFieldName === Item::FIELD_NAME_CONTACTS)
		{
			$entityFieldName = 'CONTACT_BINDINGS';
		}
		else
		{
			$entityFieldName = $this->getEntityFieldNameByMap($commonFieldName);
		}

		return $this->getDataClass()::getEntity()->hasField($entityFieldName);
	}

	/**
	 * @param string $commonFieldName
	 * @param mixed $fieldValue
	 *
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getFieldValueCaption(string $commonFieldName, $fieldValue): string
	{
		$field = $this->getFieldsCollection()->getField($commonFieldName);
		if (!$field)
		{
			return (string)$fieldValue;
		}

		if ($commonFieldName === Item::FIELD_NAME_IS_MANUAL_OPPORTUNITY)
		{
			return $fieldValue ?
				Loc::getMessage('CRM_COMMON_IS_MANUAL_OPPORTUNITY_TRUE')
				:
				Loc::getMessage('CRM_COMMON_IS_MANUAL_OPPORTUNITY_FALSE');
		}

		if ($field->getType() === Field::TYPE_STRING)
		{
			return $fieldValue ? (string)$fieldValue : Loc::getMessage('CRM_COMMON_EMPTY');
		}
		if ($field->getType() === Field::TYPE_USER)
		{
			return Container::getInstance()->getUserBroker()->getName((int)$fieldValue) ?? Loc::getMessage('CRM_COMMON_EMPTY');
		}
		if ($field->getType() === Field::TYPE_BOOLEAN)
		{
			if (!is_bool($fieldValue) && empty($fieldValue))
			{
				return Loc::getMessage('CRM_COMMON_EMPTY');
			}

			return $fieldValue ? Loc::getMessage('CRM_COMMON_YES') : Loc::getMessage('CRM_COMMON_NO');
		}
		if($field->getType() === Field::TYPE_CRM_STATUS)
		{
			$statusId = (string)$fieldValue;
			if ($statusId === '')
			{
				return Loc::getMessage('CRM_COMMON_EMPTY');
			}

			if ($commonFieldName === Item::FIELD_NAME_STAGE_ID || $commonFieldName === Item::FIELD_NAME_PREVIOUS_STAGE_ID)
			{
				$stage = $this->getStage($statusId);

				return $stage ? $stage->getName() : $statusId;
			}

			if ($commonFieldName === Item::FIELD_NAME_SOURCE_ID)
			{
				return StatusTable::getStatusesList(StatusTable::ENTITY_ID_SOURCE)[$statusId] ?? $statusId;
			}
		}
		if ($field->getType() === Field::TYPE_CRM_CURRENCY)
		{
			return Currency::getCurrencyCaption((string)$fieldValue) ?? Loc::getMessage('CRM_COMMON_EMPTY');
		}
		if ($field->getType() === Field::TYPE_CRM_COMPANY)
		{
			return Container::getInstance()->getCompanyBroker()->getTitle((int)$fieldValue) ?? Loc::getMessage('CRM_COMMON_EMPTY');
		}
		if ($field->getType() === Field::TYPE_CRM_CONTACT)
		{
			return Container::getInstance()->getContactBroker()->getFormattedName((int)$fieldValue) ?? Loc::getMessage('CRM_COMMON_EMPTY');
		}
		if($field instanceof Field\Category)
		{
			$category = $this->getCategory((int)$fieldValue);
			if($category)
			{
				return $category->getName();
			}
		}

		return (string)$fieldValue;
	}

	/**
	 * Returns collection of items defined by $parameters,
	 * where $parameters - array of the same structure as in DataManager::getList()
	 *
	 * @param array $parameters
	 *
	 * @return Item[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getItems(array $parameters = []): array
	{
		$items = [];

		$parameters = $this->prepareGetListParameters($parameters);

		$list = $this->getDataClass()::getList($parameters);
		while($item = $list->fetchObject())
		{
			$items[] = $this->getItemByEntityObject($item);
		}

		return $items;
	}

	/**
	 * Method performs getList with additional filter by users permissions.
	 *
	 * First - collect all permissionTypes depending on current filter.
	 * If categories are not supported - there is only one type.
	 * If there is filter by categories - get all types for them.
	 * If there is not filter by categories - we should collect all existing categories.
	 * After collecting all types we can calculate sql for ids an apply it to the filter.
	 *
	 * @param array $parameters - getList orm parameters.
	 * @param int|null $userId - user identifier for which should be calculated permissions.
	 * If not passed - get current user.
	 * @param string|null $operation - type of operation to check permissions.
	 * @return array
	 */
	public function getItemsFilteredByPermissions(
		array $parameters,
		?int $userId = null,
		string $operation = UserPermissions::OPERATION_READ
	): array
	{
		$userPermissions = Container::getInstance()->getUserPermissions($userId);
		if ($userPermissions->getUserId() === 0)
		{
			// no data for unauthorized user
			return [];
		}
		$filter = $parameters['filter'] ?? [];
		$entityTypes = $this->collectEntityTypesForPermissions($filter, $userId);
		if ($this->isCategoriesSupported())
		{
			$select = $parameters['select'] ?? [];
			// there is no need to add to select if it is empty - it will be filled in prepareGetListParameters
			if (!empty($select))
			{
				$parameters['select'][] = Item::FIELD_NAME_CATEGORY_ID;
			}
		}

		$parameters['filter'] = $userPermissions->applyAvailableItemsFilter($filter, $entityTypes, $operation);

		return $this->getItems($parameters);
	}

	protected function collectEntityTypesForPermissions(array $filter, ?int $userId = null): array
	{
		$userPermissions = Container::getInstance()->getUserPermissions($userId);
		$entityTypes = [$userPermissions::getPermissionEntityType($this->getEntityTypeId())];
		if ($this->isCategoriesSupported())
		{
			$entityTypes = [];
			$operationInfo = EntityHandler::findFieldOperation(
				Item::FIELD_NAME_CATEGORY_ID,
				$filter
			);
			if(
				is_array($operationInfo)
				&& (
					$operationInfo['OPERATION'] === '='
					|| $operationInfo['OPERATION'] === 'IN'
				)
			)
			{
				$categoryIDs = (array)($operationInfo['CONDITION']);

				foreach($categoryIDs as $categoryId)
				{
					if($categoryId >= 0)
					{
						$entityTypes[] = $userPermissions::getPermissionEntityType(
							$this->getEntityTypeId(),
							$categoryId
						);
					}
				}
			}
			else
			{
				$categories = $this->getCategories();
				foreach ($categories as $category)
				{
					$entityTypes[] = $userPermissions::getPermissionEntityType(
						$this->getEntityTypeId(),
						$category->getId()
					);
				}
			}
		}

		return $entityTypes;
	}

	/**
	 * Returns Item by $id. Contacts, products and observers are selected by default
	 *
	 * @param int $id
	 *
	 * @return Item|null
	 */
	public function getItem(int $id): ?Item
	{
		$parameters = [
			'select' => ['*'],
			'filter' => ['=ID' => $id]
		];

		if ($this->isFieldExists(Item::FIELD_NAME_CONTACTS))
		{
			$parameters['select'][] = Item::FIELD_NAME_CONTACTS;
		}
		if ($this->isFieldExists(Item::FIELD_NAME_PRODUCTS))
		{
			$parameters['select'][] = Item::FIELD_NAME_PRODUCTS;
		}
		if ($this->isFieldExists(Item::FIELD_NAME_OBSERVERS))
		{
			$parameters['select'][] = Item::FIELD_NAME_OBSERVERS;
		}

		$parameters = $this->prepareGetListParameters($parameters);

		$object = $this->getDataClass()::getList($parameters)->fetchObject();

		return $object ? $this->getItemByEntityObject($object) : null;
	}

	/**
	 * Returns number of items filtered by $filter.
	 *
	 * @param array $filter
	 *
	 * @return int
	 */
	public function getItemsCount(array $filter = []): int
	{
		$tableName = $this->getDataClass()::getTableName();
		if (!Application::getConnection()->isTableExists($tableName))
		{
			return 0;
		}

		$params = $this->replaceCommonFieldNames(['filter' => $filter]);
		$normalizedFilter = $params['filter'] ?? [];

		return (int)$this->getDataClass()::getCount($normalizedFilter);
	}

	/**
	 * Returns number of items filtered by $filter with additional filter by permissions.
	 *
	 * @param array $filter - Filter to count items with.
	 * @param int|null $userId - User identifier to check permissions.
	 * @param string $operation - Operation type.
	 * @return int
	 */
	public function getItemsCountFilteredByPermissions(
		array $filter = [],
		?int $userId = null,
		string $operation = UserPermissions::OPERATION_READ
	): int
	{
		$params = $this->replaceCommonFieldNames(['filter' => $filter]);
		$filter = $params['filter'] ?? [];

		$entityTypes = $this->collectEntityTypesForPermissions($filter, $userId);
		$filter = Container::getInstance()->getUserPermissions($userId)->applyAvailableItemsFilter(
			$filter,
			$entityTypes,
			$operation
		);

		return (int)$this->getDataClass()::getCount($filter);
	}

	protected function prepareGetListParameters(array $parameters): array
	{
		$parameters['select'] = !empty($parameters['select']) ? $parameters['select'] : ['*'];

		if (in_array('*', $parameters['select'], true))
		{
			$parameters['select'][] = 'UF_*';
		}

		$selectWithoutContacts = array_diff($parameters['select'], [Item::FIELD_NAME_CONTACTS]);
		$isContactsInSelect = ($parameters['select'] !== $selectWithoutContacts);
		$parameters['select'] = $selectWithoutContacts;

		if ($isContactsInSelect && $this->isClientEnabled())
		{
			$parameters['select'][] = 'CONTACT_BINDINGS';
			$parameters['select'][] = Item::FIELD_NAME_CONTACT_ID;
		}

		if (in_array(Item::FIELD_NAME_PRODUCTS, $parameters['select'], true) && $this->isLinkWithProductsEnabled())
		{
			$parameters['select'][] = Item::FIELD_NAME_PRODUCTS.'.IBLOCK_ELEMENT';
			$parameters['select'][] = Item::FIELD_NAME_PRODUCTS.'.CP_PRODUCT_NAME';
		}

		return $this->replaceCommonFieldNames($parameters);
	}

	/**
	 * Replaces common field names in getList parameters with entity-specific names if it's needed
	 *
	 * @param array $getListParameters
	 * @param bool $isFilter Should be passed on a recursive call only
	 *
	 * @return array
	 */
	protected function replaceCommonFieldNames(array $getListParameters, bool $isFilter = null): array
	{
		$preparedGetListParameters = [];

		foreach ($getListParameters as $key => $value)
		{
			$key = $this->replaceCommonFieldName($key);

			if (is_array($value))
			{
				$isFilterRecursive = $isFilter;
				// if we in a filter, we should pass this info down the stack unmodified
				if (!is_bool($isFilterRecursive))
				{
					// the value is not set yet since this is the first level of recursion
					// we are not sure whether we are in filter, initialize the value
					$isFilterRecursive = ($key === 'filter');
				}

				$value = $this->replaceCommonFieldNames($value, $isFilterRecursive);
			}
			// since filter values don't contain field references and mostly contain literal values,
			// we don't replace names here to avoid cases like
			// 'COMMENT' => 'THE COMPANY IS OUR PRIORITY' -> 'COMMENT' => 'THE COMPANY_ID IS OUR PRIORITY'
			elseif (!$isFilter && is_string($value))
			{
				$value = $this->replaceCommonFieldName($value);
			}

			$preparedGetListParameters[$key] = $value;

			// to avoid correlations between iterations
			unset($isFilterRecursive);
		}

		return $preparedGetListParameters;
	}

	protected function replaceCommonFieldName(string $key): string
	{
		$isReference = (mb_strpos($key, '.') !== false);

		if ($isReference)
		{
			$regex = '|^[!=%@><]*#COMMON_FIELD_NAME#\.|';
		}
		else
		{
			$regex = '|^[!=%@><]*#COMMON_FIELD_NAME#$|';
		}

		$regex .= BX_UTF_PCRE_MODIFIER;

		foreach ($this->getFieldsMap() as $commonFieldName => $entityFieldName)
		{
			if (preg_match(str_replace('#COMMON_FIELD_NAME#', $commonFieldName, $regex), $key))
			{
				$key = str_replace($commonFieldName, $entityFieldName, $key);
			}
		}

		return $key;
	}

	/**
	 * Creates new Item for this entity.
	 *
	 * @param array $data
	 * @return Item
	 */
	public function createItem(array $data = []): Item
	{
		/** @var EntityObject $object */
		$object = $this->getDataClass()::createObject();
		$item = $this->getItemByEntityObject($object);
		foreach ($this->getFieldsCollection() as $field)
		{
			if (isset($data[$field->getName()]))
			{
				$item->set($field->getName(), $data[$field->getName()]);
			}
			elseif ($field->isUserField())
			{
				$ufHandlerClass = $field->getUserField()['USER_TYPE']['CLASS_NAME'];

				if (is_subclass_of($ufHandlerClass, UserField\Types\BaseType::class))
				{
					$defaultValue = $ufHandlerClass::getDefaultValue($field->getUserField());
					if (!empty($defaultValue))
					{
						$item->set($field->getName(), $defaultValue);
					}
				}
			}
		}

		return $item;
	}

	/**
	 * Set first available stage of $item for user with $userId.
	 * Return new stage identifier if it was set.
	 *
	 * @param Item $item - Item to set stageId.
	 * @param int|null $userId - user identifier.
	 * @return string|null
	 */
	public function setStartStageIdPermittedForUser(Item $item, ?int $userId = null): ?string
	{
		$userPermissions = Container::getInstance()->getUserPermissions($userId);
		if ($userPermissions->getUserId() === 0)
		{
			// no available stages for not authorized user
			return null;
		}
		if (!$this->isStagesSupported())
		{
			return null;
		}
		$categoryId = $this->isCategoriesSupported() ? $item->getCategoryId() : 0;
		$stages = $this->getStages($categoryId);
		$startStageId = $userPermissions->getStartStageId(
			$this->getEntityTypeId(),
			$stages,
			$categoryId,
			$item->isNew() ? $userPermissions::OPERATION_ADD : $userPermissions::OPERATION_UPDATE
		);

		if ($startStageId)
		{
			$item->setStageId($startStageId);
			return $startStageId;
		}

		return null;
	}

	/**
	 * Returns ENTITY_ID for user fields.
	 *
	 * @return string
	 */
	public function getUserFieldEntityId(): string
	{
		return \CCrmOwnerType::ResolveUserFieldEntityID($this->getEntityTypeId());
	}

	/**
	 * Returns name of this entity
	 *
	 * @return string
	 */
	public function getEntityName(): string
	{
		return \CCrmOwnerType::ResolveName($this->getEntityTypeId());
	}

	/**
	 * Returns abbreviation for this entity
	 *
	 * @return string
	 */
	public function getEntityAbbreviation(): string
	{
		return \CCrmOwnerTypeAbbr::ResolveByTypeID($this->getEntityTypeId());
	}

	/**
	 * Returns human-readable language specific description for this entity
	 *
	 * @return string
	 */
	public function getEntityDescription(): string
	{
		return \CCrmOwnerType::GetDescription($this->getEntityTypeId());
	}

	/**
	 * Returns true if this entity supports multiple assigned.
	 *
	 * @return bool
	 */
	public function isMultipleAssignedEnabled(): bool
	{
		return false;
	}

	//region categories

	/**
	 * Returns true if this entity supports categories.
	 *
	 * @return bool
	 */
	public function isCategoriesSupported(): bool
	{
		return false;
	}

	/**
	 * Returns true if this entity supports categories and they enabled in interfaces.
	 *
	 * @return bool
	 */
	public function isCategoriesEnabled(): bool
	{
		return false;
	}

	/**
	 * Returns array of categories for this entity.
	 *
	 * @return Category[]
	 */
	public function getCategories(): array
	{
		if($this->categories === null)
		{
			$this->categories = $this->loadCategories();
		}

		return $this->categories;
	}

	/**
	 * Creates new category.
	 *
	 * @param array $data. Initial data for created category.
	 * @return Category
	 */
	abstract public function createCategory(array $data = []): Category;

	/**
	 * Returns category by id.
	 *
	 * @param int $id
	 * @return Category|null
	 */
	public function getCategory(int $id): ?Category
	{
		foreach($this->getCategories() as $category)
		{
			if($category->getId() === $id)
			{
				return $category;
			}
		}

		return null;
	}

	abstract protected function loadCategories(): array;

	/**
	 * Get default category if it exists.
	 * Creates new default category if there is none and returns it.
	 *
	 * @return Category
	 * @throws InvalidOperationException
	 */
	public function createDefaultCategoryIfNotExist(): Category
	{
		$currentDefaultCategory = $this->getDefaultCategory();
		if(!$currentDefaultCategory)
		{
			// if there some categories - make the first default
			$categories = $this->getCategories();
			if(!empty($categories))
			{
				$category = $categories[0];
				$category->setIsDefault(true);
				$category->save();

				return $category;
			}
			// no categories - create new and make it default
			$category = $this->createCategory();
			$category->setEntityTypeId($this->getEntityTypeId());
			$category->setIsDefault(true);
			$result = $category->save();
			if(!$result->isSuccess())
			{
				throw new InvalidOperationException('Error trying create default category for entity ' . $this->getEntityTypeId());
			}

			$this->categories[] = $category;

			return $category;
		}

		return $currentDefaultCategory;
	}

	/**
	 * Returns default category of current entity if it exists.
	 *
	 * @return Category|null
	 */
	public function getDefaultCategory(): ?Category
	{
		foreach($this->getCategories() as $category)
		{
			if($category->getIsDefault())
			{
				return $category;
			}
		}

		return null;
	}

	public function clearCategoriesCache(): self
	{
		$this->categories = null;

		return $this;
	}
	//endregion

	protected function getUserType(): \CCrmUserType
	{
		if (!$this->userType)
		{
			$userFieldEntityId = $this->getUserFieldEntityId();
			$this->userType = new \CCrmUserType(
				Application::getUserTypeManager(),
				$userFieldEntityId
			);
		}

		return $this->userType;
	}

	public function clearUserFieldsInfoCache()
	{
		$this->userType = null;
		return $this;
	}

	public function getUserFieldsInfo(): array
	{
		$fieldsInfo = [];
		if (empty($this->getUserFieldEntityId()))
		{
			return $fieldsInfo;
		}

		$this->getUserType()->PrepareFieldsInfo($fieldsInfo);

		return $fieldsInfo;
	}

	public function getUserFields(): array
	{
		if (empty($this->getUserFieldEntityId()))
		{
			return [];
		}
		return $this->getUserType()->GetFields();
	}

	public function getFieldsCollection(): Field\Collection
	{
		if ($this->fieldsCollection === null)
		{
			$fields = [];
			$userFields = $this->getUserFields();
			foreach ($this->getFieldsInfo() as $name => $info)
			{
				$fields[$name] = $this->createField($name, $info);
			}
			foreach ($this->getUserFieldsInfo() as $name => $info)
			{
				$info['USER_FIELD'] = $userFields[$name];
				$fields[$name] = $this->createField($name, $info);
			}

			$this->fieldsCollection = new Field\Collection($fields);
		}

		return $this->fieldsCollection;
	}

	protected function createField(string $name, array $description): Field
	{
		$className = Field::class;
		if (isset($description['CLASS']) && is_a($description['CLASS'], $className, true))
		{
			$className = $description['CLASS'];
		}

		return new $className($name, $description);
	}

	/**
	 * Returns add operation for this entity.
	 *
	 * @param Item $item
	 * @param Context|null $context
	 * @return Operation\Add
	 */
	public function getAddOperation(Item $item, Context $context = null): Operation\Add
	{
		return new Operation\Add($item, $this->getOperationSettings($context), $this->getFieldsCollection());
	}

	/**
	 * Returns update operations for this entity.
	 *
	 * @param Item $item
	 * @param Context|null $context
	 * @return Operation\Update
	 */
	public function getUpdateOperation(Item $item, Context $context = null): Operation\Update
	{
		return new Operation\Update($item, $this->getOperationSettings($context), $this->getFieldsCollection());
	}

	/**
	 * Returns delete operation for this entity
	 *
	 * @param Item $item
	 * @param Context|null $context
	 * @return Operation\Delete
	 */
	public function getDeleteOperation(Item $item, Context $context = null): Operation\Delete
	{
		$operation = new Operation\Delete($item, $this->getOperationSettings($context), $this->getFieldsCollection());

		if ($this->isRecyclebinEnabled() && \Bitrix\Crm\Recycling\BaseController::isEnabled())
		{
			$operation->addAction(
				Operation::ACTION_BEFORE_SAVE,
				new Operation\Action\MoveToBin($item)
			);
		}

		return $operation;
	}

	public function getConversionOperation(Item $item, EntityConversionConfig $configs, Context $context = null): Operation\Conversion
	{
		$operation = new Operation\Conversion($item, $this->getOperationSettings($context), $this->getFieldsCollection());
		$operation->setConfigs($configs);

		return $operation;
	}

	/**
	 * Returns a configured Operation\Copy instance
	 *
	 * @param Item $item
	 * @param Context|null $context
	 *
	 * @return Operation\Copy
	 */
	public function getCopyOperation(Item $item, Context $context = null): Operation\Copy
	{
		return new Operation\Copy($item, $this->getOperationSettings($context), $this->getFieldsCollection());
	}

	protected function getOperationSettings(?Context $context): Operation\Settings
	{
		if (!$context)
		{
			$context = Container::getInstance()->getContext();
		}

		$settings = new Operation\Settings($context);

		if (!$this->isAutomationEnabled())
		{
			$settings->disableAutomation();
		}

		if (!$this->isBizProcEnabled())
		{
			$settings->disableBizProc();
		}

		return $settings;
	}

	/**
	 * Returns TrackedObject, specific for this entity
	 *
	 * @param Item $itemBeforeSave
	 * @param Item|null $item
	 *
	 * @return TrackedObject
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function getTrackedObject(Item $itemBeforeSave, Item $item = null): TrackedObject
	{
		$trackedObject = new TrackedObject\Item($itemBeforeSave, $item);

		$trackedObject->bindToEntityType($this->getEntityName(), $this->getEntityDescription());
		$trackedObject->setTrackedFieldNames($this->getTrackedFieldNames());

		foreach ($this->getDependantTrackedObjects() as $dependantTrackedObject)
		{
			$trackedObject->addDependantTrackedObject($dependantTrackedObject);
		}

		return $trackedObject;
	}

	abstract protected function getTrackedFieldNames(): array;

	/**
	 * @return TrackedObject[]
	 */
	abstract protected function getDependantTrackedObjects(): array;

	//region stages

	/**
	 * Returns ENTITY_ID value for b_crm_status table.
	 * If stages are not supported, returns null
	 *
	 * @param int|null $categoryId - by default $categoryId from a default category is used. If categories are not
	 * supported, this parameter is ignored
	 *
	 * @return string|null
	 * @throws NotImplementedException
	 */
	public function getStagesEntityId(?int $categoryId = null): ?string
	{
		if ($this->isStagesSupported())
		{
			throw new NotImplementedException(__METHOD__ . ' should be overwritten if stages are supported');
		}

		return null;
	}

	/**
	 * Returns true if this entity supports stages.
	 *
	 * @return bool
	 */
	public function isStagesSupported(): bool
	{
		return true;
	}

	public function isStagesEnabled(): bool
	{
		return true;
	}

	/**
	 * Returns stages for $categoryId.
	 *
	 * @param int|null $categoryId
	 *
	 * @return EO_Status_Collection
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getStages(int $categoryId = null): EO_Status_Collection
	{
		if(!isset($this->stageCollections[$categoryId]))
		{
			$filter = [
				'=ENTITY_ID' => $this->getStagesEntityId($categoryId),
			];

			$this->stageCollections[$categoryId] = $this->statusTableClassName::getList([
				'order' => [
					'SORT' => 'ASC',
				],
				'filter' => $filter,
				'cache' => static::STAGES_CACHE_TTL,
			])->fetchCollection();
			foreach($this->stageCollections[$categoryId] as $stage)
			{
				$this->stages[$stage->getStatusId()] = $stage;
			}
		}

		return $this->stageCollections[$categoryId];
	}

	public function purgeStagesCache(): Factory
	{
		$this->stageCollections = [];
		$this->stages = [];

		return $this;
	}

	public function getStage(string $statusId): ?EO_Status
	{
		if(isset($this->stages[$statusId]))
		{
			return $this->stages[$statusId];
		}

		$stage = $this->statusTableClassName::getList([
			'filter' => [
				'=STATUS_ID' => $statusId,
			],
		])->fetchObject();
		if($stage)
		{
			$this->stages[$stage->getStatusId()] = $stage;

			return $stage;
		}

		return null;
	}
	//endregion

	/**
	 * Returns true if this entity supports links with Catalog products
	 *
	 * @return bool
	 */
	public function isLinkWithProductsEnabled(): bool
	{
		return false;
	}

	/**
	 * Returns true if this entity supports 'Begin Date' and 'Close Date' for its elements
	 *
	 * @return bool
	 */
	public function isBeginCloseDatesEnabled(): bool
	{
		return false;
	}

	/**
	 * Returns true if this entity supports 'Client' field for its elements
	 *
	 * @return bool
	 */
	public function isClientEnabled(): bool
	{
		return false;
	}

	/**
	 * Returns true if this entity supports Crm Tracking
	 *
	 * @return bool
	 */
	public function isCrmTrackingEnabled(): bool
	{
		return false;
	}

	/**
	 * Returns true if this entity supports 'My Company' field for its elements
	 *
	 * @return bool
	 */
	public function isMyCompanyEnabled(): bool
	{
		return false;
	}

	/**
	 * Returns true if this entity supports integration with 'documentgenerator' module
	 *
	 * @return bool
	 */
	public function isDocumentGenerationEnabled(): bool
	{
		return true;
	}

	/**
	 * Returns true if this entity supports 'SOURCE_ID' and 'SOURCE_DESCRIPTION' fields for its elements
	 *
	 * @return bool
	 */
	public function isSourceEnabled(): bool
	{
		return false;
	}

	/**
	 * Returns true if this entity can be used in user field type 'crm'.
	 * @see \Bitrix\Crm\UserField\Types\ElementType
	 *
	 * @return bool
	 */
	public function isUseInUserfieldEnabled(): bool
	{
		return false;
	}

	/**
	 * Returns true if this entity supports recycle bin.
	 *
	 * @return bool
	 */
	public function isRecyclebinEnabled(): bool
	{
		return false;
	}

	/**
	 * Returns true if this entity supported by automation.
	 *
	 * @return bool
	 */
	public function isAutomationEnabled(): bool
	{
		return false;
	}

	/**
	 * Returns true if this entity supported by business processes designer.
	 *
	 * @return bool
	 */
	public function isBizProcEnabled(): bool
	{
		return false;
	}

	/**
	 * Returns true if this entity supports OBSERVERS field.
	 *
	 * @return bool
	 */
	public function isObserversEnabled(): bool
	{
		return false;
	}

	/**
	 * Return true if urls for detail pages of this entity should be routed by common templates
	 *
	 * @return bool
	 */
	public function isNewRoutingForDetailEnabled(): bool
	{
		return true;
	}

	/**
	 * Return true if urls for list pages of this entity should be routed by common templates
	 *
	 * @return bool
	 */
	public function isNewRoutingForListEnabled(): bool
	{
		return true;
	}

	/**
	 * Return true if urls for automation page of this entity should be routed by common templates
	 *
	 * @return bool
	 */
	public function isNewRoutingForAutomationEnabled(): bool
	{
		return true;
	}

	public function getEditorAdapter(): EditorAdapter
	{
		if (!$this->editorAdapter)
		{
			$this->editorAdapter = new EditorAdapter($this->getFieldsCollection(), $this->getDependantFieldsMap());

			if ($this->isClientEnabled())
			{
				$this->editorAdapter->addEntityField(
					EditorAdapter::getClientField(
						$this->getFieldCaption(EditorAdapter::FIELD_CLIENT)
					)
				);
			}
			if ($this->isLinkWithProductsEnabled())
			{
				$this->editorAdapter->addEntityField(
					EditorAdapter::getOpportunityField(
						$this->getFieldCaption(EditorAdapter::FIELD_OPPORTUNITY)
					)
				);
				$this->editorAdapter->addEntityField(
					EditorAdapter::getProductRowSummaryField(
						$this->getFieldCaption(EditorAdapter::FIELD_PRODUCT_ROW_SUMMARY)
					)
				);
			}
		}

		return $this->editorAdapter;
	}

	public function getDependantFieldsMap(): array
	{
		$map = [];
		if ($this->isLinkWithProductsEnabled())
		{
			$map[EditorAdapter::FIELD_OPPORTUNITY] = [
				Item::FIELD_NAME_OPPORTUNITY,
			];
		}

		if ($this->isClientEnabled())
		{
			$map[EditorAdapter::FIELD_CLIENT] = [
				Item::FIELD_NAME_COMPANY_ID,
				Item::FIELD_NAME_CONTACT_ID,
			];
		}

		return $map;
	}

	/**
	 * Return category identifier of the item with $id.
	 * If categories are not supported - returns 0.
	 * If no item - returns null.
	 *
	 * @param int $id - Item identifier.
	 * @return int|null
	 */
	public function getItemCategoryId(int $id): ?int
	{
		if (!$this->isCategoriesSupported())
		{
			return 0;
		}

		$items = $this->getItems([
			'select' => [Item::FIELD_NAME_CATEGORY_ID],
			'filter' => [
				'=ID' => $id,
			]
		]);
		if (!empty($items))
		{
			return $items[0]->getCategoryId();
		}

		return null;
	}
}
