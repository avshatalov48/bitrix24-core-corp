<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Activity\Provider\ProviderManager;
use Bitrix\Crm\Activity\TodoCreateNotification;
use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Cleaning\CleaningManager;
use Bitrix\Crm\Conversion\EntityConversionConfig;
use Bitrix\Crm\Counter\EntityCounterSettings;
use Bitrix\Crm\Currency;
use Bitrix\Crm\EO_Status;
use Bitrix\Crm\EO_Status_Collection;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Service\EventHistory\TrackedObject;
use Bitrix\Crm\Service\Operation\Action\Compatible\SocialNetwork\ProcessSendNotification;
use Bitrix\Crm\Settings\Crm;
use Bitrix\Crm\Settings\HistorySettings;
use Bitrix\Crm\Statistics;
use Bitrix\Crm\StatusTable;
use Bitrix\Crm\UI\Filter\EntityHandler;
use Bitrix\Crm\UserField\Visibility\VisibilityManager;
use Bitrix\Crm\UtmTable;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Query;
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
	protected $isParentFieldsAdded = false;
	protected $itemsCategoryCache = [];
	private array $itemsStageCache = [];

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

		if ($this->isCrmTrackingEnabled())
		{
			$settings += UtmTable::getUtmFieldsInfo();
		}
		$settings += Container::getInstance()->getParentFieldManager()->getParentFieldsInfo($this->getEntityTypeId());

		foreach ($settings as $name => &$field)
		{
			$field['TITLE'] = $this->getFieldCaption($name);
		}

		return $settings;
	}

	/**
	 * Returns data about item fields with fields map applied
	 *
	 * @return array
	 */
	public function getFieldsInfoByMap(): array
	{
		$fieldsInfo = $this->getFieldsInfo();
		$result = [];
		foreach ($fieldsInfo as $fieldId => $fieldInfo)
		{
			$result[$this->getEntityFieldNameByMap($fieldId)] = $fieldInfo;
		}

		return $result;
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
		$disabledFields = [];

		/** @see Item::isCategoriesSupported() */
		if (!$this->isCategoriesSupported())
		{
			$disabledFields[] = new Item\DisabledField(Item::FIELD_NAME_CATEGORY_ID);
		}

		/** @see Item::isStagesEnabled() */
		if (!$this->isStagesEnabled())
		{
			$disabledFields[] = new Item\DisabledField(Item::FIELD_NAME_STAGE_ID);
		}

		$item = new $this->itemClassName(
			$this->getEntityTypeId(),
			$object,
			$this->getFieldsMap(),
			$disabledFields
		);

		$this->configureItem($item, $object);

		return $item;
	}

	protected function configureItem(Item $item, EntityObject $entityObject): void
	{
		if ($this->isMultiFieldsEnabled())
		{
			$item->addImplementation(new Item\FieldImplementation\Multifield($this->getEntityTypeId(), $item->getId()));
		}

		$fileFields = $this->getFieldsCollection()->getFieldsByType(Field::TYPE_FILE);
		if (count($fileFields) > 0)
		{
			$item->addImplementation(new Item\FieldImplementation\File($entityObject, $fileFields, $this->getFieldsMap()));
		}

		if ($item->isCategoriesSupported())
		{
			$item->refreshCategoryDependentDisabledFields();
		}

		$flexibleContentTypeFields = [];
		foreach ($this->getFieldsCollection()->getFieldsByType(Field::TYPE_TEXT) as $field)
		{
			if ($field->getValueType() === Field::VALUE_TYPE_BB)
			{
				$flexibleContentTypeFields[] = $field;
			}
		}
		$item->addImplementation(
			new Item\FieldImplementation\Comments($item, $entityObject, new Field\Collection($flexibleContentTypeFields)),
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

		if (ParentFieldManager::isParentFieldName($commonFieldName))
		{
			$parentEntityTypeId = ParentFieldManager::getEntityTypeIdFromFieldName($commonFieldName);
			return \CCrmOwnerType::GetDescription($parentEntityTypeId);
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
		if ($commonFieldName === Item::FIELD_NAME_CONTACTS || $commonFieldName === Item::FIELD_NAME_CONTACT_IDS)
		{
			$entityFieldName = 'CONTACT_BINDINGS';
		}
		else
		{
			$entityFieldName = $this->getEntityFieldNameByMap($commonFieldName);
		}

		if (ParentFieldManager::isParentFieldName($entityFieldName))
		{
			return Container::getInstance()->getRelationManager()->getRelation(
				new RelationIdentifier(
					ParentFieldManager::getEntityTypeIdFromFieldName($entityFieldName),
					$this->getEntityTypeId(),
				)
			) !== null;
		}

		return $this->getDataClass()::getEntity()->hasField($entityFieldName);
	}

	/**
	 * @param string $commonFieldName
	 * @param mixed $fieldValue
	 *
	 * @return string
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

			$entityId = $field->getCrmStatusType();

			return (StatusTable::getStatusesList($entityId)[$statusId] ?? $statusId);
		}
		if ($field->getType() === Field::TYPE_CRM_CURRENCY)
		{
			return Currency::getCurrencyCaption((string)$fieldValue) ?? Loc::getMessage('CRM_COMMON_EMPTY');
		}
		if ($field->getType() === Field::TYPE_CRM_COMPANY)
		{
			return
				Container::getInstance()->getCompanyBroker()->getTitle((int)$fieldValue)
				?? Loc::getMessage('CRM_COMMON_EMPTY')
			;
		}
		if ($field->getType() === Field::TYPE_CRM_CONTACT)
		{
			return
				Container::getInstance()->getContactBroker()->getFormattedName((int)$fieldValue)
				?? Loc::getMessage('CRM_COMMON_EMPTY')
			;
		}
		if($field instanceof Field\Category)
		{
			$category = $this->getCategory((int)$fieldValue);
			if($category)
			{
				return $category->getName();
			}
		}
		if ($field->getType() === Field::TYPE_LOCATION)
		{
			return \CCrmLocations::getLocationStringByCode($fieldValue);
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
	 */
	public function getItems(array $parameters = []): array
	{
		$fmIndex = array_search(Item::FIELD_NAME_FM, $parameters['select'] ?? [], true);
		if ($fmIndex !== false)
		{
			unset($parameters['select'][$fmIndex]);
		}

		$isFmInSelect = $fmIndex !== false;

		$parameters = $this->prepareGetListParameters($parameters);

		if ($this->isSkipSelectIdsHack($parameters))
		{
			$list = $this->getDataClass()::getList($parameters);
		}
		else
		{
			$itemIds =
				$this->getDataClass()::getList(['select' => [Item::FIELD_NAME_ID]] + $parameters)
					->fetchCollection()
					->getIdList()
			;
			if (empty($itemIds))
			{
				return [];
			}

			$params = [
					'filter' => ['@' . Item::FIELD_NAME_ID => $itemIds],
					'limit' => null,
				] + $parameters;

			unset($params['runtime']['permissions']);

			$list = $this->getDataClass()::getList($params);
		}

		$items = [];
		while($item = $list->fetchObject())
		{
			$items[] = $this->getItemByEntityObject($item);
		}

		if ($isFmInSelect && $this->isMultiFieldsEnabled())
		{
			Container::getInstance()->getMultifieldStorage()->warmupCache(
				$this->getEntityTypeId(),
				array_map(fn(Item $item) => $item->getId(), $items),
			);
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

		$parameters = $userPermissions->applyAvailableItemsGetListParameters(
			$parameters,
			$entityTypes,
			$operation,
		);

		return $this->getItems($parameters);
	}

	protected function collectEntityTypesForPermissions(array &$filter, ?int $userId = null): array
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
				$shouldStrictByCategories = false;
				$availableCategoriesIds = [];
				foreach ($categories as $category)
				{
					if (!$userPermissions->canReadTypeInCategory($this->getEntityTypeId(), $category->getId()))
					{
						$shouldStrictByCategories = true;
					}
					else
					{
						$availableCategoriesIds[] = $category->getId();
					}
					$entityTypes[] = $userPermissions::getPermissionEntityType(
						$this->getEntityTypeId(),
						$category->getId()
					);
				}

				if ($shouldStrictByCategories && !empty($availableCategoriesIds))
				{
					if (mb_strtoupper($filter['LOGIC'] ?? '') === 'OR')
					{
						$filter = [
							0 => $filter,
							'@CATEGORY_ID' => $availableCategoriesIds,
						];
					}
					else
					{
						$filter['@CATEGORY_ID'] = $availableCategoriesIds;
					}
				}
			}
		}

		return $entityTypes;
	}

	/**
	 * Returns Item by $id. Contacts, products and observers are selected by default
	 *
	 * @param int $id
	 * @param array $fieldsToSelect Fields to select. All fields by default.
	 *
	 * @return Item|null
	 */
	public function getItem(int $id, array $fieldsToSelect = ['*']): ?Item
	{
		$parameters = [
			'select' => $fieldsToSelect,
			'filter' => ['=ID' => $id],
			// Do not set limit here! 'limit' limits number of DB rows, not items.
			// If sql contains joins, there are multiple rows for each item. Some data will not be fetched
			// 'limit' => 1,
		];

		$items = $this->getItems($parameters);

		return array_shift($items);
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
		$this->addParentFieldsReferences();
		$tableName = $this->getDataClass()::getTableName();
		if (!Application::getConnection()->isTableExists($tableName))
		{
			return 0;
		}

		$params = $this->replaceCommonFieldNames(['filter' => $filter]);
		$normalizedFilter = $params['filter'] ?? [];

		return $this->getDataClass()::getCount($normalizedFilter);
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
		$this->addParentFieldsReferences();
		$params = $this->replaceCommonFieldNames(['filter' => $filter]);
		$filter = $params['filter'] ?? [];

		$entityTypes = $this->collectEntityTypesForPermissions($filter, $userId);
		$filter = Container::getInstance()->getUserPermissions($userId)->applyAvailableItemsFilter(
			$filter,
			$entityTypes,
			$operation
		);

		return $this->getDataClass()::getCount($filter);
	}

	protected function prepareGetListParameters(array $parameters): array
	{
		$this->addParentFieldsReferences();

		$rawSelect = empty($parameters['select']) ? ['*'] : $parameters['select'];
		$parameters['select'] = $this->prepareSelect($rawSelect);

		$rawFilter = empty($parameters['filter']) ? [] : $parameters['filter'];
		$parameters['filter'] = $this->prepareFilter($rawFilter);

		return $this->replaceCommonFieldNames($parameters);
	}

	protected function addParentFieldsReferences(): void
	{
		if (!$this->isParentFieldsAdded)
		{
			$this->isParentFieldsAdded = true;

			Container::getInstance()->getParentFieldManager()->addParentFieldsReferences(
				static::getDataClass()::getEntity(),
				$this->getEntityTypeId()
			);
		}
	}

	protected function prepareSelect(array $select): array
	{
		if (in_array('*', $select, true))
		{
			$select[] = 'UF_*';
			$select[] = 'PARENT_ID_*';

			if ($this->isFieldExists(Item::FIELD_NAME_COMPANY))
			{
				$select[] = Item::FIELD_NAME_COMPANY;
			}
			if ($this->isFieldExists(Item::FIELD_NAME_CONTACTS))
			{
				$select[] = Item::FIELD_NAME_CONTACTS;
			}
			if ($this->isFieldExists(Item::FIELD_NAME_PRODUCTS))
			{
				$select[] = Item::FIELD_NAME_PRODUCTS;
			}
			if ($this->isFieldExists(Item::FIELD_NAME_OBSERVERS))
			{
				$select[] = Item::FIELD_NAME_OBSERVERS;
			}
		}

		$selectWithoutContacts = array_diff($select, [Item::FIELD_NAME_CONTACTS, Item::FIELD_NAME_CONTACT_IDS]);
		$isContactsInSelect = ($select !== $selectWithoutContacts);

		$isCompanyInSelect = in_array(Item::FIELD_NAME_COMPANY, $select, true);

		$select = $selectWithoutContacts;

		if ($isContactsInSelect)
		{
			$select[] = Item::FIELD_NAME_CONTACT_BINDINGS;
			if ($this->isFieldExists(Item::FIELD_NAME_CONTACT_ID))
			{
				$select[] = Item::FIELD_NAME_CONTACT_ID;
			}
		}

		if ($isCompanyInSelect)
		{
			$select[] = Item::FIELD_NAME_COMPANY_ID;
			$select[] = Item::FIELD_NAME_COMPANY;
		}

		if (in_array(Item::FIELD_NAME_PRODUCTS, $select, true))
		{
			$select[] = Item::FIELD_NAME_PRODUCTS . '.IBLOCK_ELEMENT';
			$select[] = Item::FIELD_NAME_PRODUCTS . '.PRODUCT_ROW_RESERVATION';
		}

		return $select;
	}

	private function prepareFilter(array $filter): array
	{
		$processed = [];
		foreach ($filter as $key => $value)
		{
			if (is_array($value))
			{
				$value = $this->prepareFilter($value);
			}

			if (is_string($key) && !$this->isReference($key))
			{
				if (mb_strpos($key, Item::FIELD_NAME_CONTACT_IDS) !== false)
				{
					$key = str_replace(Item::FIELD_NAME_CONTACT_IDS, Item::FIELD_NAME_CONTACT_BINDINGS . '.CONTACT_ID', $key);
				}
				elseif (mb_strpos($key, Item\Contact::FIELD_NAME_COMPANY_IDS) !== false)
				{
					$key = str_replace(
						Item\Contact::FIELD_NAME_COMPANY_IDS,
						Item\Contact::FIELD_NAME_COMPANY_BINDINGS . '.COMPANY_ID',
						$key,
					);
				}
				elseif (mb_strpos($key, Item::FIELD_NAME_OBSERVERS) !== false)
				{
					$key = str_replace(Item::FIELD_NAME_OBSERVERS, Item::FIELD_NAME_OBSERVERS . '.USER_ID', $key);
				}
			}

			$processed[$key] = $value;
		}

		return $processed;
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
		if ($this->isReference($key))
		{
			$regex = '|^[!=%@><]*#COMMON_FIELD_NAME#\.|u';
		}
		else
		{
			$regex = '|^[!=%@><]*#COMMON_FIELD_NAME#$|u';
		}

		foreach ($this->getFieldsMap() as $commonFieldName => $entityFieldName)
		{
			if (preg_match(str_replace('#COMMON_FIELD_NAME#', $commonFieldName, $regex), $key))
			{
				/** @var string $key */
				$key = str_replace($commonFieldName, $entityFieldName, $key);
			}
		}

		return $key;
	}

	private function isReference(string $key): bool
	{
		return (strpos($key, '.') !== false);
	}

	/**
	 * Creates new Item for this entity.
	 *
	 * @param array $data
	 * @return Item
	 */
	public function createItem(array $data = []): Item
	{
		$this->addParentFieldsReferences();

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
		if (
			!$this->isStagesSupported()
			|| !$item->isStagesEnabled()
		)
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
	 * Return human-readable language specific description for category of items of this entity.
	 *
	 * @return string
	 */
	public function getEntityDescriptionInPlural(): string
	{
		return \CCrmOwnerType::GetCategoryCaption($this->getEntityTypeId());
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
		return $this->isCategoriesSupported();
	}

	/**
	 * Returns true if the category with the provided $categoryId is available for usage (is not locked or restricted)
	 *
	 * @param int $categoryId
	 * @return bool
	 */
	public function isCategoryAvailable(int $categoryId): bool
	{
		if (!$this->isCategoriesSupported())
		{
			return false;
		}

		if (!$this->isCategoryExists($categoryId))
		{
			return false;
		}

		return $this->checkIfCategoryAvailable($categoryId);
	}

	protected function checkIfCategoryAvailable(int $categoryId): bool
	{
		return true;
	}

	public function isCategoryExists(int $categoryId): bool
	{
		foreach ($this->getCategories() as $category)
		{
			if ($category->getId() === $categoryId)
			{
				return true;
			}
		}

		return false;
	}

	public function getCategoryFieldsInfo(): array
	{
		return [
			'ID' => [
				'TYPE' => Field::TYPE_INTEGER,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly],
			],
			'NAME' => [
				'TYPE' => Field::TYPE_STRING,
			],
			'SORT' => [
				'TYPE' => Field::TYPE_INTEGER,
			],
			'ENTITY_TYPE_ID' => [
				'TYPE' => Field::TYPE_INTEGER,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::Required],
			],
			'IS_DEFAULT' => [
				'TYPE' => Field::TYPE_BOOLEAN,
			],
			'IS_SYSTEM' => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly],
			],
			'CODE' => [
				'TYPE' => Field::TYPE_STRING,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly],
			],
		];
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

	/**
	 * Returns category by specified unique code.
	 *
	 * @param string $code
	 *
	 * @return Category|null
	 */
	public function getCategoryByCode(string $code): ?Category
	{
		$code = trim($code);
		if (empty(trim($code)))
		{
			return null;
		}

		foreach($this->getCategories() as $category)
		{
			if ($category->getCode() === $code)
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

	/**
	 * Returns fields info of all user fields of this entity type. (Even those, that are not visible for current user).
	 *
	 * @return array
	 */
	public function getUserFieldsInfo(): array
	{
		$fieldsInfo = [];
		if (empty($this->getUserFieldEntityId()))
		{
			return $fieldsInfo;
		}

		$this->getUserType()->PrepareFieldsInfo($fieldsInfo, ['skipUserFieldVisibilityCheck' => true]);

		return $fieldsInfo;
	}

	/**
	 * Returns all user fields of this entity type. (Even those, that are not visible for current user).
	 *
	 * @return array
	 */
	public function getUserFields(): array
	{
		if (empty($this->getUserFieldEntityId()))
		{
			return [];
		}

		return $this->getUserType()->GetAbstractFields([
			'skipUserFieldVisibilityCheck' => true,
		]);
	}

	/**
	 * Returns collection of all fields of this entity type. (Even those, that are not visible for current user).
	 *
	 * @return Field\Collection
	 */
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

	public function clearFieldsCollectionCache(): self
	{
		$this->clearUserFieldsInfoCache();
		$this->fieldsCollection = null;

		return $this;
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
		$add = new Operation\Add($item, $this->getOperationSettings($context), $this->getFieldsCollection());

		$this->configureAddOperation($add);

		return $add;
	}

	/**
	 * Configure all operations, that extends Operation\Add: Add itself, Import and Restore
	 *
	 * @param Operation $operation
	 * @return void
	 */
	protected function configureAddOperation(Operation $operation): void
	{
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
		$operation = new Operation\Update($item, $this->getOperationSettings($context), $this->getFieldsCollection());

		$operation->addAction(
			Operation::ACTION_AFTER_SAVE,
			new Operation\Action\DeleteEntityBadges(),
		);

		return $operation;
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

		$operation->setCleaner(CleaningManager::getCleaner($this->getEntityTypeId(), $item->getId()));

		if ($this->isRecyclebinEnabled() && \Bitrix\Crm\Recycling\BaseController::isEnabled())
		{
			$operation->addAction(
				Operation::ACTION_BEFORE_SAVE,
				new Operation\Action\MoveToBin(),
			);
		}
		else
		{
			$operation->addAction(
				Operation::ACTION_AFTER_SAVE,
				new Operation\Action\DeleteFiles($this->getFieldsCollection()->getFieldsByType(Field::TYPE_FILE)),
			);
		}

		if (!HistorySettings::getCurrent()->isDeletionEventEnabled($this->getEntityTypeId()))
		{
			$operation->disableSaveToHistory();
		}

		return $operation;
	}

	public function getConversionOperation(
		Item $item,
		EntityConversionConfig $configs,
		Context $context = null
	): Operation\Conversion
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

	public function getRestoreOperation(Item $item, Context $context = null): Operation\Restore
	{
		$restore = new Operation\Restore($item, $this->getOperationSettings($context), $this->getFieldsCollection());

		$this->configureAddOperation($restore);
		$restore->removeAction(
			Operation::ACTION_AFTER_SAVE,
			ProcessSendNotification\WhenAddingEntity::class,
		);

		return $restore;
	}

	public function getImportOperation(Item $item, Context $context = null): Operation\Import
	{
		$import = new Operation\Import($item, $this->getOperationSettings($context), $this->getFieldsCollection());

		$this->configureAddOperation($import);

		return $import;
	}

	protected function getOperationSettings(?Context $context): Operation\Settings
	{
		if (!$context)
		{
			$context = Container::getInstance()->getContext();
		}

		$settings = new Operation\Settings($context);

		$settings->setStatisticsFacade($this->getStatisticsFacade());

		if (!$this->isAutomationEnabled())
		{
			$settings->disableAutomation();
		}

		if (!$this->isBizProcEnabled())
		{
			$settings->disableBizProc();
		}

		if (!$this->isDeferredCleaningEnabled())
		{
			$settings->disableDeferredCleaning();
		}

		$settings->setActivityProvidersToAutocomplete(ProviderManager::getCompletableProviderIdFlatList());

		return $settings;
	}

	protected function getStatisticsFacade(): ?Statistics\OperationFacade
	{
		return null;
	}

	/**
	 * Returns TrackedObject, specific for this entity
	 *
	 * @param Item $itemBeforeSave
	 * @param Item|null $item
	 *
	 * @return TrackedObject
	 */
	public function getTrackedObject(Item $itemBeforeSave, Item $item = null): TrackedObject
	{
		$trackedObject = new TrackedObject\Item($itemBeforeSave, $item);

		$trackedObject->bindToEntityType($this->getEntityName(), $this->getEntityDescription());

		$trackedFieldNames = [];
		foreach ($this->getTrackedFieldNames() as $trackedFieldName)
		{
			if (
				$itemBeforeSave->isFieldDisabled($trackedFieldName)
				|| (
					$item
					&& $item->isFieldDisabled($trackedFieldName)
				)
			)
			{
				continue;
			}

			$trackedFieldNames[] = $trackedFieldName;
		}
		$trackedObject->setTrackedFieldNames($trackedFieldNames);

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
		return $this->isStagesSupported();
	}

	/**
	 * Returns stages for $categoryId.
	 *
	 * @param int|null $categoryId
	 *
	 * @return EO_Status_Collection
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
				'cache' => ['ttl' => static::STAGES_CACHE_TTL],
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
		if (isset($this->stages[$statusId]))
		{
			return $this->stages[$statusId];
		}

		if ($this->isCategoriesSupported())
		{
			$stagesArrays = [];
			foreach ($this->getCategories() as $category)
			{
				$stagesArrays[] = $this->getStages($category->getId())->getAll();
			}

			$stages = $stagesArrays ? array_merge(...$stagesArrays) : [];
		}
		else
		{
			$stages = $this->getStages()->getAll();
		}

		foreach ($stages as $stage)
		{
			if ($stage->getStatusId() === $statusId)
			{
				return $stage;
			}
		}

		return null;
	}

	/**
	 * Returns semantics of a stage with $stageId
	 * Returns null if the stage is not found
	 *
	 * @param string $stageId
	 * @return string|null
	 */
	final public function getStageSemantics(string $stageId): ?string
	{
		$stage = $this->getStage($stageId);
		if (!$stage)
		{
			return null;
		}

		$semantics = $stage->getSemantics();

		return PhaseSemantics::isDefined($semantics) ? $semantics : PhaseSemantics::PROCESS;
	}

	final public function getSuccessfulStage(?int $categoryId = null): ?EO_Status
	{
		foreach ($this->getStages($categoryId) as $stage)
		{
			if (PhaseSemantics::isSuccess($stage->getSemantics()))
			{
				return $stage;
			}
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

	/**
	 * Return true if this entity has own multi fields.
	 *
	 * @return bool
	 */
	public function isMultiFieldsEnabled(): bool
	{
		return false;
	}

	/**
	 * Return true if payments procession is enabled for this entity.
	 *
	 * @return bool
	 */
	public function isPaymentsEnabled(): bool
	{
		return false;
	}

	/**
	 * Returns true if after item deletion data that was linked to the item is deleted on agent by default
	 *
	 * @return bool
	 */
	public function isDeferredCleaningEnabled(): bool
	{
		return true;
	}

	/**
	 * Returns true if this entity supports counters.
	 *
	 * @return bool
	 */
	public function isCountersEnabled(): bool
	{
		return false;
	}

	/**
	 * Returns true if this entity supports LAST_ACTIVITY_* fields and all associated logic
	 *
	 * @return bool
	 */
	public function isLastActivitySupported(): bool
	{
		return true;
	}

	/**
	 * Returns true if 'last activity' functionality is enabled in UI
	 *
	 * @return bool
	 */
	public function isLastActivityEnabled(): bool
	{
		if (!$this->isLastActivitySupported())
		{
			return false;
		}

		$isEnabled = Option::get('crm', 'enable_last_activity_for_' . mb_strtolower($this->getEntityName()), 'Y');

		return ($isEnabled === 'Y');
	}

	public function isSmartActivityNotificationEnabled(): bool
	{
		if (!$this->isSmartActivityNotificationSupported() || !Crm::isUniversalActivityScenarioEnabled())
		{
			return false;
		}

		$todoCreateNotification = new TodoCreateNotification($this->getEntityTypeId());

		return !$todoCreateNotification->isSkipped();
	}

	public function isSmartActivityNotificationSupported(): bool
	{
		return false;
	}

	/**
	 * Return actual counters settings.
	 *
	 * @return EntityCounterSettings
	 */
	public function getCountersSettings(): EntityCounterSettings
	{
		return
			$this->isCountersEnabled()
				? EntityCounterSettings::createDefault($this->isStagesSupported())
				: new EntityCounterSettings()
			;
	}

	/**
	 * Return true if inventory management is enabled for this entity.
	 *
	 * @return bool
	 */
	public function isInventoryManagementEnabled(): bool
	{
		return false;
	}

	public function getEditorAdapter(): EditorAdapter
	{
		if (!$this->editorAdapter)
		{
			$fieldsThatAreAccessibleToCurrentUser = VisibilityManager::filterNotAccessibleFields(
				$this->getEntityTypeId(),
				$this->getFieldsCollection()->getFieldNameList(),
			);
			$fieldsThatAreAccessibleToCurrentUserMap = array_flip($fieldsThatAreAccessibleToCurrentUser);
			$editorFields = [];
			foreach ($this->getFieldsCollection() as $field)
			{
				if (isset($fieldsThatAreAccessibleToCurrentUserMap[$field->getName()]))
				{
					$editorFields[] = $field;
				}
			}

			$this->editorAdapter = new EditorAdapter(
				new Field\Collection($editorFields),
				$this->getDependantFieldsMap(),
			);

			if ($this->isClientEnabled())
			{
				$this->editorAdapter->addEntityField(
					EditorAdapter::getClientField(
						$this->getFieldCaption(EditorAdapter::FIELD_CLIENT),
						EditorAdapter::FIELD_CLIENT,
						EditorAdapter::FIELD_CLIENT_DATA_NAME,
						['entityTypeId' => $this->getEntityTypeId()]
					)
				);
			}
			if ($this->isLinkWithProductsEnabled())
			{
				$this->editorAdapter->addEntityField(
					EditorAdapter::getOpportunityField(
						$this->getFieldCaption(EditorAdapter::FIELD_OPPORTUNITY),
						EditorAdapter::FIELD_OPPORTUNITY,
						$this->isPaymentsEnabled()
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
		if ($id === 0)
		{
			return null;
		}
		if (!$this->isCategoriesSupported())
		{
			return null;
		}

		if (array_key_exists($id, $this->itemsCategoryCache))
		{
			return $this->itemsCategoryCache[$id];
		}
		$this->itemsCategoryCache[$id] = null;

		$items = $this->getItems([
			'select' => [Item::FIELD_NAME_CATEGORY_ID],
			'filter' => [
				'=ID' => $id,
			],
		]);
		if (!empty($items))
		{
			$this->itemsCategoryCache[$id] = $items[0]->getCategoryId();
		}

		return $this->itemsCategoryCache[$id];
	}

	/**
	 * Return category of the item with $id.
	 *
	 * @param int $id - Item identifier.
	 * @return Category|null
	 */
	public function getItemCategory(int $id): ?Category
	{
		$categoryId = (int)$this->getItemCategoryId($id);
		if (!$categoryId)
		{
			return null;
		}

		return $this->getCategory($categoryId);
	}

	public function clearItemCategoryCache(int $id): void
	{
		unset($this->itemsCategoryCache[$id]);
	}

	final public function getItemStageId(int $id): ?string
	{
		if ($id === 0)
		{
			return null;
		}
		if (!$this->isStagesEnabled())
		{
			return null;
		}

		if (array_key_exists($id, $this->itemsStageCache))
		{
			return $this->itemsStageCache[$id];
		}

		$item = $this->getItem($id, [Item::FIELD_NAME_STAGE_ID]);

		$this->itemsStageCache[$id] = $item?->getStageId();

		return $this->itemsStageCache[$id];
	}

	public function clearItemStageCache(int $id): void
	{
		unset($this->itemsStageCache[$id]);
	}

	public function isInCustomSection(): bool
	{
		return false;
	}

	private function isSkipSelectIdsHack(array $parameters): bool
	{
		$filter = $parameters['filter'] ?? [];
		$select = $parameters['select'] ?? [];

		if (count($filter) === 1 && $this->isFilterContainField($filter, Item::FIELD_NAME_ID))
		{
			return true;
		}

		if ((count($select) === 1 && array_key_exists(Item::FIELD_NAME_ID, $select)))
		{
			return true;
		}

		return false;
	}

	private function isFilterContainField(array $array, string $field): bool
	{
		$keys = array_keys($array);
		foreach ($keys as $key)
		{
			// remove any special symbols from $key
			$key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);

			if ($key === $field)
			{
				return true;
			}
		}

		return false;
	}

	public function checkIfTotalItemsCountExceeded(int $limit, array $filter = []): bool
	{
		$query = $this->getDataClass()::query()
			->setSelect(['ID'])
			->setFilter($filter)
			->setLimit($limit + 1)
		;

		$newQuery = (new Query(Entity::getInstanceByQuery($query)));
		$newQuery->registerRuntimeField('', new ExpressionField('QTY', 'COUNT(%s)', 'ID'));
		$newQuery->addSelect('QTY');
		$count = $newQuery->fetch()['QTY'];

		return $count > $limit;
	}

	public function isCommunicationRoutingSupported(): bool
	{
		return false;
	}
}
