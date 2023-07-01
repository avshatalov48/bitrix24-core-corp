<?php

namespace Bitrix\Crm\Service;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\EO_Status_Collection;
use Bitrix\Crm\Item;
use Bitrix\Crm\Security\AttributesProvider;
use Bitrix\Crm\Security\EntityPermission\MyCompany;
use Bitrix\Crm\Security\Manager;
use Bitrix\Crm\Security\QueryBuilder;
use Bitrix\Main\Loader;

class UserPermissions
{
	public const OPERATION_READ = 'READ';
	public const OPERATION_ADD = 'ADD';
	public const OPERATION_UPDATE = 'WRITE';
	public const OPERATION_DELETE = 'DELETE';
	public const OPERATION_EXPORT = 'EXPORT';
	public const OPERATION_IMPORT = 'IMPORT';

	public const PERMISSION_NONE = \CCrmPerms::PERM_NONE;
	public const PERMISSION_SELF = \CCrmPerms::PERM_SELF;
	public const PERMISSION_OPENED = \CCrmPerms::PERM_OPEN;
	public const PERMISSION_SUBDEPARTMENT = \CCrmPerms::PERM_SUBDEPARTMENT;
	public const PERMISSION_DEPARTMENT = \CCrmPerms::PERM_DEPARTMENT;
	public const PERMISSION_ALL = \CCrmPerms::PERM_ALL;
	public const PERMISSION_CONFIG = \CCrmPerms::PERM_CONFIG;

	public const ATTRIBUTES_OPENED = 'O';
	public const ATTRIBUTES_READ_ALL = \CCrmPerms::ATTR_READ_ALL;

	/** @var int */
	protected $userId;
	/**
	 * @var \CCrmPerms|null
	 * Please, don't use this property directly, as it can be null. Use the method instead
	 * @see UserPermissions::getCrmPermissions()
	 */
	protected $crmPermissions;
	/** @var bool */
	protected $isAdmin;
	/** @var AttributesProvider|null */
	protected $attributesProvider;
	protected ?MyCompany $myCompanyPermissions = null;

	public function setCrmPermissions(\CCrmPerms $crmPermissions): UserPermissions
	{
		$this->crmPermissions = $crmPermissions;

		return $this;
	}

	public function getCrmPermissions(): \CCrmPerms
	{
		if (!$this->crmPermissions)
		{
			$this->crmPermissions = new \CCrmPerms($this->userId);
		}

		return $this->crmPermissions;
	}

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function isAdmin(): bool
	{
		if ($this->isAdmin !== null)
		{
			return $this->isAdmin;
		}

		$this->isAdmin = false;
		if ($this->getUserId() <= 0)
		{
			return $this->isAdmin; // false
		}

		$currentUser = \CCrmSecurityHelper::GetCurrentUser();
		if ((int)$currentUser->GetID() === $this->getUserId())
		{
			$this->isAdmin = $currentUser->isAdmin();
			if (!$this->isAdmin)
			{
				$this->isAdmin = in_array(1, $currentUser->GetUserGroupArray(), false);
			}

			return $this->isAdmin;
		}

		try
		{
			if (
				\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24')
				&& Loader::IncludeModule('bitrix24')
			)
			{
				if (
					class_exists('CBitrix24')
					&& method_exists('CBitrix24', 'IsPortalAdmin')
				)
				{
					// New style check
					$this->isAdmin = \CBitrix24::IsPortalAdmin($this->getUserId());
				}
			}
			else
			{
				// Check user group 1 ('Portal admins')
				$arGroups = $currentUser::GetUserGroup($this->getUserId());
				$this->isAdmin = in_array(1, $arGroups, false);
			}
		}
		catch (\Exception $exception)
		{
		}

		return $this->isAdmin;
	}

	public function canWriteConfig(): bool
	{
		return $this->getCrmPermissions()->havePerm('CONFIG', static::PERMISSION_CONFIG, static::OPERATION_UPDATE);
	}

	public function canReadConfig(): bool
	{
		return $this->getCrmPermissions()->havePerm('CONFIG', static::PERMISSION_CONFIG, static::OPERATION_READ);
	}

	/**
	 * Check that user can view items.
	 * If entity support categories, we should check all categories of this type,
	 * and return true if user can view items in at least one of them.
	 *
	 * @param int $entityTypeId - Type identifier.
	 * @return bool
	 */
	public function canReadType(int $entityTypeId): bool
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if ($factory && $factory->isCategoriesSupported())
		{
			return $this->hasPermissionInAtLeastOneCategory($factory, static::OPERATION_READ);
		}

		$entityName = static::getPermissionEntityType($entityTypeId);

		return \CCrmAuthorizationHelper::CheckReadPermission($entityName, 0, $this->getCrmPermissions());
	}

	/**
	 * Check that user can view items in the category.
	 *
	 * @param int $entityTypeId - Type identifier.
	 * @param int $categoryId Category identifier.
	 * @return bool
	 */
	public function canReadTypeInCategory(int $entityTypeId, int $categoryId): bool
	{
		$entityName = static::getPermissionEntityType($entityTypeId, $categoryId);

		return \CCrmAuthorizationHelper::CheckReadPermission($entityName, 0, $this->getCrmPermissions());
	}

	/**
	 * Returns true if user can create a new entity type
	 *
	 * @return bool
	 */
	public function canAddType(): bool
	{
		return $this->canWriteConfig();
	}

	/**
	 * Returns true if user can update settings of type $entityTypeId
	 *
	 * @param int $entityTypeId
	 * @return bool
	 */
	public function canUpdateType(int $entityTypeId): bool
	{
		if (\CCrmOwnerType::isDynamicTypeBasedStaticEntity($entityTypeId))
		{
			return false;
		}

		return $this->canWriteConfig();
	}

	/**
	 * Return true if user can export items of type $entityTypeId in any category
	 *
	 * @param int $entityTypeId
	 * @return bool
	 */
	public function canExportType(int $entityTypeId): bool
	{
		if ($this->isAdmin())
		{
			return true;
		}

		$factory = Container::getInstance()->getFactory($entityTypeId);
		if ($factory && $factory->isCategoriesSupported())
		{
			return $this->hasPermissionInAtLeastOneCategory($factory, static::OPERATION_EXPORT);
		}

		$entityName = static::getPermissionEntityType($entityTypeId);

		return \CCrmAuthorizationHelper::CheckExportPermission($entityName, $this->getCrmPermissions());
	}

	/**
	 * Return true if user can export items of type $entityTypeId and $categoryId.
	 *
	 * @param int $entityTypeId
	 * @param int $categoryId
	 * @return bool
	 */
	public function canExportTypeInCategory(int $entityTypeId, int $categoryId): bool
	{
		if ($this->isAdmin())
		{
			return true;
		}

		$entityName = static::getPermissionEntityType($entityTypeId, $categoryId);

		return \CCrmAuthorizationHelper::CheckExportPermission($entityName, $this->getCrmPermissions());
	}

	public function canImportItem(Item $item): bool
	{
		return $this->getPermissionType($item, static::OPERATION_IMPORT) !== static::PERMISSION_NONE;
	}

	public function canAddItem(Item $item): bool
	{
		return $this->getPermissionType($item, static::OPERATION_ADD) !== static::PERMISSION_NONE;
	}

	/**
	 * Returns true if user has permission to add new item to type $entityTypeId for at least one category
	 *
	 * @param int $entityTypeId
	 * @return bool
	 */
	protected function canAddToType(int $entityTypeId): bool
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);

		if ($factory && $factory->isCategoriesSupported())
		{
			return $this->hasPermissionInAtLeastOneCategory($factory, static::OPERATION_ADD);
		}

		$entityName = static::getPermissionEntityType($entityTypeId);

		return !$this->getCrmPermissions()->HavePerm(
			$entityName,
			BX_CRM_PERM_NONE,
			static::OPERATION_ADD
		);
	}

	/**
	 * Returns true if user has permission to add new item to type $entityTypeId and $categoryId
	 *
	 * @param int $entityTypeId
	 * @param int $categoryId
	 * @return bool
	 */
	protected function canAddToTypeInCategory(int $entityTypeId, int $categoryId): bool
	{
		$entityName = static::getPermissionEntityType($entityTypeId, $categoryId);

		return !$this->getCrmPermissions()->HavePerm(
			$entityName,
			BX_CRM_PERM_NONE,
			static::OPERATION_ADD
		);
	}

	protected function hasPermissionInAtLeastOneCategory(Factory $factory, string $operation): bool
	{
		foreach ($factory->getCategories() as $category)
		{
			$categoryEntityName = static::getPermissionEntityType($factory->getEntityTypeId(), $category->getId());

			$hasPermissionInCategory = !$this->getCrmPermissions()->HavePerm(
				$categoryEntityName,
				BX_CRM_PERM_NONE,
				$operation,
			);

			if ($hasPermissionInCategory)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns true if user has permission to add new item to type $entityTypeId in $categoryId on stage $stageId.
	 * If $stageId is not specified than checks access for at least one stage.
	 *
	 * @param int $entityTypeId
	 * @param int|null $categoryId
	 * @param string|null $stageId
	 * @return bool
	 */
	public function checkAddPermissions(int $entityTypeId, ?int $categoryId = null, ?string $stageId = null): bool
	{
		if ($entityTypeId === \CCrmOwnerType::ShipmentDocument)
		{
			if (Loader::includeModule('catalog'))
			{
				return
					AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ)
					&& AccessController::getCurrent()->check(ActionDictionary::ACTION_INVENTORY_MANAGEMENT_ACCESS)
					&& AccessController::getCurrent()->checkByValue(
						ActionDictionary::ACTION_STORE_DOCUMENT_MODIFY,
						\Bitrix\Catalog\StoreDocumentTable::TYPE_SALES_ORDERS
					)
				;
			}

			return \Bitrix\Crm\Order\Permissions\Shipment::checkCreatePermission($this->getCrmPermissions());
		}
		elseif($entityTypeId === \CCrmOwnerType::StoreDocument)
		{
			return Loader::includeModule('catalog') && AccessController::getCurrent()->check(ActionDictionary::ACTION_STORE_VIEW);
		}

		if (is_null($stageId))
		{
			return
				is_null($categoryId)
				? $this->canAddToType($entityTypeId)
				: $this->canAddToTypeInCategory($entityTypeId, $categoryId)
			;
		}

		$categoryId = $categoryId ?? 0;

		$entityName = static::getPermissionEntityType($entityTypeId, $categoryId);

		$attributes = [];
		$stageAttribute = $this->getStageIdAttributeByEntityTypeId($entityTypeId, $stageId);
		if ($stageAttribute)
		{
			$attributes[] = $stageAttribute;
		}

		$permission = $this->getCrmPermissions()->GetPermType($entityName, static::OPERATION_ADD, $attributes);

		return $permission > static::PERMISSION_NONE;
	}

	/**
	 * Return true if user has permission to export element with $id of type $entityTypeId in category $categoryId.
	 *
	 * @param int $entityTypeId
	 * @param int $id
	 * @param int|null $categoryId
	 * @return bool
	 */
	public function checkExportPermissions(int $entityTypeId, int $id = 0, ?int $categoryId = null): bool
	{
		$canExportType = is_null($categoryId)
			? $this->canExportType($entityTypeId)
			: $this->canExportTypeInCategory($entityTypeId, $categoryId)
		;

		if (!$canExportType)
		{
			return false;
		}

		if ($id === 0)
		{
			return $canExportType;
		}

		if ($id > 0 && $categoryId === null)
		{
			$categoryId = $this->getItemCategoryIdOrDefault($entityTypeId, $id);
		}
		$entityName = static::getPermissionEntityType($entityTypeId, $categoryId);
		$attributes = Manager::resolveController($entityName)->getPermissionAttributes($entityName, [$id]);
		$entityAttributes = $attributes[$id] ?? [];

		return $this->getCrmPermissions()->CheckEnityAccess($entityName, static::OPERATION_EXPORT, $entityAttributes);
	}

	/**
	 * Return field name for stage field.
	 *
	 * @internal
	 * @param int $entityTypeId
	 * @return string|null
	 */
	protected function getStageFieldName(int $entityTypeId): ?string
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);

		$stageFieldName = $factory
			? $factory->getEntityFieldNameByMap(Item::FIELD_NAME_STAGE_ID)
			: null;

		if (
			!$stageFieldName
			&& (
				$entityTypeId === \CCrmOwnerType::Lead
				|| $entityTypeId === \CCrmOwnerType::Invoice
			)
		)
		{
			// todo remove after adding all factories
			$stageFieldName = 'STATUS_ID';
		}

		return $stageFieldName;
	}

	/**
	 * Return attribute with particular $stageId for entity by $entityTypeId.
	 *
	 * @param int $entityTypeId
	 * @param string $stageId
	 * @return string|null
	 */
	protected function getStageIdAttributeByEntityTypeId(int $entityTypeId, string $stageId): ?string
	{
		$stageFieldName = $this->getStageFieldName($entityTypeId);
		if ($stageFieldName)
		{
			return $this->combineStageIdAttribute($stageFieldName, $stageId);
		}

		return null;
	}

	protected function combineStageIdAttribute(string $stageFieldName, string $stageId): string
	{
		return $stageFieldName . $stageId;
	}

	/**
	 * Returns true if user has permission to update an item with $id of type $entityTypeId in $categoryId.
	 * If $categoryId not defined, value will be loaded from DB for item $id
	 *
	 * @param int $entityTypeId
	 * @param int $id
	 * @param int|null $categoryId
	 * @return bool
	 */
	public function checkUpdatePermissions(int $entityTypeId, int $id, ?int $categoryId = null): bool
	{
		if ($entityTypeId === \CCrmOwnerType::ShipmentDocument)
		{
			if (Loader::includeModule('catalog'))
			{
				return
					AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ)
					&& AccessController::getCurrent()->check(ActionDictionary::ACTION_INVENTORY_MANAGEMENT_ACCESS)
					&& AccessController::getCurrent()->checkByValue(
						ActionDictionary::ACTION_STORE_DOCUMENT_MODIFY,
						\Bitrix\Catalog\StoreDocumentTable::TYPE_SALES_ORDERS
					)
				;
			}

			return  \Bitrix\Crm\Order\Permissions\Shipment::checkUpdatePermission($id, $this->getCrmPermissions());
		}
		elseif($entityTypeId === \CCrmOwnerType::StoreDocument)
		{
			return Loader::includeModule('catalog') && AccessController::getCurrent()->check(ActionDictionary::ACTION_STORE_VIEW);
		}

		if (is_null($categoryId))
		{
			$categoryId = $this->getItemCategoryIdOrDefault($entityTypeId, $id);
		}

		$entityName = static::getPermissionEntityType($entityTypeId, $categoryId);

		return \CCrmAuthorizationHelper::CheckUpdatePermission(
			$entityName,
			$id,
			$this->getCrmPermissions()
		);
	}

	/**
	 * Returns true if user has permission to update the $item.
	 *
	 * @param Item $item
	 * @return bool
	 */
	public function canUpdateItem(Item $item): bool
	{
		return $this->checkUpdatePermissions($item->getEntityTypeId(), $item->getId(), $item->getCategoryIdForPermissions());
	}

	/**
	 * Returns true if user has permission to delete item with $id of type $entityTypeId in $categoryId.
	 *
	 * @param int $entityTypeId
	 * @param int $id
	 * @param int|null $categoryId
	 * @return bool
	 */
	public function checkDeletePermissions(int $entityTypeId, int $id = 0, ?int $categoryId = null): bool
	{
		if($entityTypeId === \CCrmOwnerType::ShipmentDocument)
		{
			if (Loader::includeModule('catalog'))
			{
				return
					AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ)
					&& AccessController::getCurrent()->check(ActionDictionary::ACTION_INVENTORY_MANAGEMENT_ACCESS)
					&& AccessController::getCurrent()->checkByValue(
						ActionDictionary::ACTION_STORE_DOCUMENT_DELETE,
						\Bitrix\Catalog\StoreDocumentTable::TYPE_SALES_ORDERS
					)
				;
			}

			return  \Bitrix\Crm\Order\Permissions\Shipment::checkDeletePermission($id, $this->getCrmPermissions());
		}
		elseif($entityTypeId === \CCrmOwnerType::StoreDocument)
		{
			return Loader::includeModule('catalog') && AccessController::getCurrent()->check(ActionDictionary::ACTION_STORE_VIEW);
		}

		if ($id === 0 && is_null($categoryId))
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);

			if ($factory && $factory->isCategoriesSupported())
			{
				return $this->hasPermissionInAtLeastOneCategory($factory, static::OPERATION_DELETE);
			}
		}

		$categoryId = $categoryId ?? 0;

		$entityName = static::getPermissionEntityType($entityTypeId, $categoryId);

		return \CCrmAuthorizationHelper::CheckDeletePermission(
			$entityName,
			$id,
			$this->getCrmPermissions()
		);
	}

	protected function getItemCategoryIdOrDefault(int $entityTypeId, int $id): int
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if ($factory && $factory->isCategoriesSupported())
		{
			return ($factory->getItemCategoryId($id) ?? 0);
		}

		return 0;
	}

	/**
	 * Returns true if user has permission to delete the $item.
	 *
	 * @param Item $item
	 * @return bool
	 */
	public function canDeleteItem(Item $item): bool
	{
		return $this->checkDeletePermissions($item->getEntityTypeId(), $item->getId(), $item->getCategoryIdForPermissions());
	}

	/**
	 * Return true if user has permission to read element of type $entityTypeId
	 * If $id is defined, access checked for this element
	 * If $categoryId is defined, access checked for this category
	 * Otherwise, check read access for at least one category
	 *
	 * If both $id and $categoryId are passed, $categoryId must contain correct category for $id.
	 *
	 * @param int $entityTypeId
	 * @param int $id
	 * @param int|null $categoryId
	 * @return bool
	 */
	public function checkReadPermissions(int $entityTypeId, int $id = 0, ?int $categoryId = null): bool
	{
		if($entityTypeId === \CCrmOwnerType::ShipmentDocument)
		{
			if (Loader::includeModule('catalog'))
			{
				return
					AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ)
					&& AccessController::getCurrent()->check(ActionDictionary::ACTION_INVENTORY_MANAGEMENT_ACCESS)
					&& AccessController::getCurrent()->checkByValue(
						ActionDictionary::ACTION_STORE_DOCUMENT_VIEW,
						\Bitrix\Catalog\StoreDocumentTable::TYPE_SALES_ORDERS
					)
				;
			}

			return  \Bitrix\Crm\Order\Permissions\Shipment::checkReadPermission($id, $this->getCrmPermissions());
		}
		elseif($entityTypeId === \CCrmOwnerType::StoreDocument)
		{
			return Loader::includeModule('catalog') && AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ);
		}

		if ($id === 0)
		{
			return is_null($categoryId)
				? $this->canReadType($entityTypeId)
				: $this->canReadTypeInCategory($entityTypeId, $categoryId)
			;
		}

		if ($id > 0 && is_null($categoryId))
		{
			$categoryId = $this->getItemCategoryIdOrDefault($entityTypeId, $id);
		}
		$entityName = static::getPermissionEntityType($entityTypeId, $categoryId);

		return \CCrmAuthorizationHelper::CheckReadPermission(
			$entityName,
			$id,
			$this->getCrmPermissions()
		);
	}

	public function canReadItem(Item $item): bool
	{
		return $this->checkReadPermissions($item->getEntityTypeId(), $item->getId(), $item->getCategoryIdForPermissions());
	}

	public function canViewItemsInCategory(Category $category): bool
	{
		return $this->checkReadPermissions($category->getEntityTypeId(), 0, $category->getId());
	}

	public function canAddCategory(Category $category): bool
	{
		return $this->canWriteConfig();
	}

	public function canUpdateCategory(Category $category): bool
	{
		return $this->canWriteConfig();
	}

	public function canDeleteCategory(Category $category): bool
	{
		return $this->canUpdateCategory($category);
	}

	/**
	 * @param array $categories
	 * @return Category[]
	 */
	public function filterAvailableForReadingCategories(array $categories): array
	{
		return array_values(array_filter($categories, [$this, 'canViewItemsInCategory']));
	}

	public function getPermissionType(Item $item, string $operationType = self::OPERATION_READ): string
	{
		$itemPermissionAttributes = $this->prepareItemPermissionAttributes($item);

		$entityName = static::getItemPermissionEntityType($item);

		return $this->getCrmPermissions()->GetPermType(
			$entityName,
			$operationType,
			$itemPermissionAttributes
		);
	}

	public function prepareItemPermissionAttributes(Item $item): array
	{
		// todo process multiple assigned
		$assignedById = $item->getAssignedById();
		$attributes = ['U' . $assignedById];
		if ($item->getOpened())
		{
			$attributes[] = static::ATTRIBUTES_OPENED;
		}

		$stageFieldName = $item->getEntityFieldNameIfExists(Item::FIELD_NAME_STAGE_ID);
		if ($stageFieldName)
		{
			$stageId = $item->getStageId();
			if ($stageId)
			{
				$attributes[] = $this->combineStageIdAttribute($stageFieldName, $item->getStageId());
			}
		}

		if ($item->hasField(Item::FIELD_NAME_OBSERVERS))
		{
			foreach ($item->getObservers() as $observerId)
			{
				$attributes[] = 'CU' . $observerId;
			}
		}

		if ($item->hasField(Item\Company::FIELD_NAME_IS_MY_COMPANY) && $item->get(Item\Company::FIELD_NAME_IS_MY_COMPANY))
		{
			$attributes[] = static::ATTRIBUTES_READ_ALL;
		}

		$attributesProvider = Container::getInstance()->getUserPermissions((int)$assignedById)->getAttributesProvider();
		$userAttributes = $attributesProvider->getEntityAttributes();

		return array_merge($attributes, $userAttributes['INTRANET']);
	}

	public static function getItemPermissionEntityType(Item $item): string
	{
		$categoryId = $item->getCategoryIdForPermissions();
		if (is_null($categoryId))
		{
			$categoryId = 0;
		}

		return static::getPermissionEntityType($item->getEntityTypeId(), $categoryId);
	}

	public static function getPermissionEntityType(int $entityTypeId, int $categoryId = 0): string
	{
		return (new PermissionEntityTypeHelper($entityTypeId))->getPermissionEntityTypeForCategory($categoryId);
	}

	public static function getEntityNameByPermissionEntityType(string $permissionEntityType): ?string
	{
		$entityTypesWithCategories = [
			\CCrmOwnerType::Deal,
			\CCrmOwnerType::Contact,
			\CCrmOwnerType::Company,
		];

		foreach ($entityTypesWithCategories as $entityTypeIdWithCategory)
		{
			if ((new PermissionEntityTypeHelper($entityTypeIdWithCategory))->doesPermissionEntityTypeBelongToEntity($permissionEntityType))
			{
				return \CCrmOwnerType::ResolveName($entityTypeIdWithCategory);
			}
		}

		if (mb_strpos($permissionEntityType, \CCrmOwnerType::DynamicTypePrefixName) === 0)
		{
			[$prefix, $entityTypeId, $categoryPostfix] = explode('_', $permissionEntityType);

			return \CCrmOwnerType::ResolveName((int)$entityTypeId);
		}

		if (\CCrmOwnerType::ResolveID($permissionEntityType) !== \CCrmOwnerType::Undefined)
		{
			return $permissionEntityType;
		}

		foreach (\CCrmOwnerType::getDynamicTypeBasedStaticEntityTypeIds() as $entityTypeId)
		{
			$entityName = \CCrmOwnerType::ResolveName($entityTypeId);
			if (mb_strpos($permissionEntityType, $entityName) === 0)
			{
				return $entityName;
			}
		}

		return null;
	}

	public static function getCategoryIdFromPermissionEntityType(string $permissionEntityType): ?int
	{
		$dealCategoryId = \Bitrix\Crm\Category\DealCategory::convertFromPermissionEntityType($permissionEntityType);
		if ($dealCategoryId !== -1)
		{
			return $dealCategoryId;
		}

		if (mb_strpos($permissionEntityType, \CCrmOwnerType::DynamicTypePrefixName) === 0)
		{
			[$prefix, $entityTypeId, $categoryPostfix] = explode('_', $permissionEntityType);

			// is like 'C12'
			if ((string)$categoryPostfix !== '')
			{
				return (int)mb_substr($categoryPostfix, 1);
			}
		}

		foreach (\CCrmOwnerType::getDynamicTypeBasedStaticEntityTypeIds() as $entityTypeId)
		{
			$entityName = \CCrmOwnerType::ResolveName($entityTypeId);
			if (mb_strpos($permissionEntityType, $entityName) === 0)
			{
				[, $categoryId] = explode($entityName . '_', $permissionEntityType);
				if ((string)$categoryId !== '')
				{
					$categoryId = (int)mb_substr($categoryId, 1);

					return $categoryId;
				}
			}
		}

		return null;
	}

	public function applyAvailableItemsFilter(
		?array $filter,
		array $permissionEntityTypes,
		?string $operation = self::OPERATION_READ,
		?string $primary = 'ID'
	): array
	{
		$builderOptions = new \Bitrix\Crm\Security\QueryBuilder\Options();
		$builderOptions->setNeedReturnRawQuery(true);
		if ($operation)
		{
			$builderOptions->setOperations((array)$operation);
		}
		$queryBuilder = $this->createListQueryBuilder($permissionEntityTypes, $builderOptions);
		$result = $queryBuilder->build();

		if (!$result->hasRestrictions())
		{
			// no need to apply filter
			return (array)$filter;
		}

		if ($result->hasAccess())
		{
			$expression = $result->getSqlExpression();
		}
		else
		{
			// access denied
			$expression = [0];
		}

		if (is_array($filter))
		{
			$filter = [
				$filter,
				'@' . $primary => $expression,
			];
		}
		else
		{
			$filter = [
				'@' . $primary => $expression,
			];
		}

		return $filter;
	}

	/**
	 * Return first stage identifier from $stages of entity with $entityTypeId on $categoryId
	 * where user has permission to do $operation.
	 * If such stage is not found - return null.
	 *
	 * @param int $entityTypeId - entity identifier.
	 * @param EO_Status_Collection $stages - collection of stages to search to.
	 * @param int $categoryId - category identifier.
	 * @param string $operation - operation (ADD | UPDATE).
	 * @return string|null
	 */
	public function getStartStageId(
		int $entityTypeId,
		EO_Status_Collection $stages,
		int $categoryId = 0,
		string $operation = self::OPERATION_ADD
	): ?string
	{
		$stageFieldName = $this->getStageFieldName($entityTypeId);
		if (!$stageFieldName)
		{
			return null;
		}
		$permissionEntity = static::getPermissionEntityType($entityTypeId, $categoryId);
		foreach ($stages as $stage)
		{
			$attributes = [$this->combineStageIdAttribute($stageFieldName, $stage->getStatusId())];
			$permission = $this->getCrmPermissions()->GetPermType($permissionEntity, $operation, $attributes);
			if ($permission !== static::PERMISSION_NONE)
			{
				return $stage->getStatusId();
			}
		}

		return null;
	}

	public function createListQueryBuilder(
		$permissionEntityTypes,
		QueryBuilder\Options $options = null
	): QueryBuilder
	{
		return new QueryBuilder((array)$permissionEntityTypes, $this, $options);
	}

	public function getAttributesProvider(): AttributesProvider
	{
		if (!$this->attributesProvider)
		{
			$this->attributesProvider = new AttributesProvider($this->getUserId());
		}

		return $this->attributesProvider;
	}

	public function getMyCompanyPermissions(): MyCompany
	{
		if (!$this->myCompanyPermissions)
		{
			$this->myCompanyPermissions = new MyCompany($this);
		}

		return $this->myCompanyPermissions;
	}
}
