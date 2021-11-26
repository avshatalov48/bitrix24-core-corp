<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\EO_Status_Collection;
use Bitrix\Crm\Item;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Crm\Security\QueryBuilder;
use Bitrix\Main\Loader;
use Bitrix\Crm\Security\AttributesProvider;

class UserPermissions
{
	public const OPERATION_READ = 'READ';
	public const OPERATION_ADD = 'ADD';
	public const OPERATION_UPDATE = 'WRITE';
	public const OPERATION_DELETE = 'DELETE';

	public const PERMISSION_NONE = \CCrmPerms::PERM_NONE;
	public const PERMISSION_SELF = \CCrmPerms::PERM_SELF;
	public const PERMISSION_OPENED = \CCrmPerms::PERM_OPEN;
	public const PERMISSION_SUBDEPARTMENT = \CCrmPerms::PERM_SUBDEPARTMENT;
	public const PERMISSION_DEPARTMENT = \CCrmPerms::PERM_DEPARTMENT;
	public const PERMISSION_ALL = \CCrmPerms::PERM_ALL;
	public const PERMISSION_CONFIG = \CCrmPerms::PERM_CONFIG;

	public const ATTRIBUTES_OPENED = 'O';

	protected $userId;
	protected $crmPermissions;
	protected $isAdmin;
	protected $attributesProvider;

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
		if((int)$currentUser->GetID() === $this->getUserId())
		{
			$this->isAdmin = $currentUser->isAdmin();
		}
		if ($this->isAdmin)
		{
			return $this->isAdmin; //true
		}

		try
		{
			if(
				\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24')
				&& Loader::IncludeModule('bitrix24'))
			{
				if(
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
				$arGroups = $currentUser->GetUserGroup($this->getUserId());
				$this->isAdmin = in_array(1, $arGroups);
			}
		}
		catch(\Exception $e)
		{
		}

		return $this->isAdmin;
	}

	public function canWriteConfig(): bool
	{
		return $this->getCrmPermissions()->havePerm('CONFIG', static::PERMISSION_CONFIG, 'WRITE');
	}

	public function canReadConfig(): bool
	{
		return $this->getCrmPermissions()->havePerm('CONFIG', static::PERMISSION_CONFIG, 'READ');
	}

	/**
	 * Check that user can view items in the category.
	 * If categoryId = 0, then we should check all categories of this type,
	 * and return true if user can view items in at least one of them.
	 *
	 * @param int $entityTypeId - Type identifier.
	 * @param int $categoryId Category identifier.
	 * @return bool
	 */
	public function canReadType(int $entityTypeId, int $categoryId = 0): bool
	{
		if ($categoryId === 0)
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if ($factory && $factory->isCategoriesSupported())
			{
				foreach ($factory->getCategories() as $category)
				{
					$entityName = static::getPermissionEntityType($entityTypeId, $category->getId());
					if (\CCrmAuthorizationHelper::CheckReadPermission($entityName, 0))
					{
						return true;
					}
				}

				return false;
			}
		}

		$entityName = static::getPermissionEntityType($entityTypeId, $categoryId);

		return \CCrmAuthorizationHelper::CheckReadPermission($entityName, 0);
	}

	public function canAddType(): bool
	{
		return $this->canWriteConfig();
	}

	public function canUpdateType(int $entityTypeId): bool
	{
		return $this->canWriteConfig();
	}

	public function canAddItem(Item $item): bool
	{
		return $this->getPermissionType($item, static::OPERATION_ADD) !== static::PERMISSION_NONE;
	}

	/**
	 * Returns true if user has permission to add new item to type $entityTypeId in $categoryId on stage $stageId.
	 * If $stageId is not specified than checks access for at least one stage.
	 *
	 * @param int $entityTypeId
	 * @param int $categoryId
	 * @param string|null $stageId
	 * @return bool
	 */
	public function checkAddPermissions(int $entityTypeId, int $categoryId = 0, ?string $stageId = null): bool
	{
		$entityName = static::getPermissionEntityType($entityTypeId, $categoryId);

		$attributes = [];
		if ($stageId)
		{
			$stageAttribute = $this->getStageIdAttributeByEntityTypeId($entityTypeId, $stageId);
			if ($stageAttribute)
			{
				$attributes[] = $stageAttribute;
			}

			$permission = $this->getCrmPermissions()->GetPermType($entityName, static::OPERATION_ADD, $attributes);

			return $permission > static::PERMISSION_NONE;
		}

		return !$this->getCrmPermissions()->HavePerm(
			$entityName,
			BX_CRM_PERM_NONE,
			static::OPERATION_ADD
		);
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
	 *
	 * @param int $entityTypeId
	 * @param int $id
	 * @param int $categoryId
	 * @return bool
	 */
	public function checkUpdatePermissions(int $entityTypeId, int $id, int $categoryId = 0): bool
	{
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
	 * @param int $categoryId
	 * @return bool
	 */
	public function checkDeletePermissions(int $entityTypeId, int $id, int $categoryId = 0): bool
	{
		$entityName = static::getPermissionEntityType($entityTypeId, $categoryId);

		return \CCrmAuthorizationHelper::CheckDeletePermission(
			$entityName,
			$id,
			$this->getCrmPermissions()
		);
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

	public function checkReadPermissions(int $entityTypeId, int $id = 0, int $categoryId = 0): bool
	{
		if ($id === 0)
		{
			return $this->canReadType($entityTypeId, $categoryId);
		}

		if ($id > 0 && $categoryId === 0)
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if ($factory && $factory->isCategoriesSupported())
			{
				$categoryId = $factory->getItemCategoryId($id) ?? 0;
			}
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
		return array_filter($categories, [$this, 'canViewItemsInCategory']);
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
		if($stageFieldName)
		{
			$stageId = $item->getStageId();
			if($stageId)
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

		$userAttributes = \Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions((int)$assignedById)
			->getAttributesProvider()
			->getEntityAttributes()
		;

		return array_merge($attributes, $userAttributes['INTRANET']);
	}

	public static function getItemPermissionEntityType(Item $item): string
	{
		return static::getPermissionEntityType($item->getEntityTypeId(), $item->getCategoryIdForPermissions());
	}

	public static function getPermissionEntityType(int $entityTypeId, int $categoryId = 0): string
	{
		$name = \CCrmOwnerType::ResolveName($entityTypeId);

		if($categoryId > 0)
		{
			$name .= '_C' . $categoryId;
		}

		return $name;
	}

	public static function getEntityNameByPermissionEntityType(string $permissionEntityType): ?string
	{
		if(\Bitrix\Crm\Category\DealCategory::hasPermissionEntity($permissionEntityType))
		{
			return \CCrmOwnerType::DealName;
		}

		if (mb_strpos($permissionEntityType, \CCrmOwnerType::DynamicTypePrefixName) === 0)
		{
			[$prefix, $entityTypeId, ] = explode('_', $permissionEntityType);

			return $prefix . '_' . $entityTypeId;
		}

		if (\CCrmOwnerType::ResolveID($permissionEntityType) !== \CCrmOwnerType::Undefined)
		{
			return $permissionEntityType;
		}

		return null;
	}

	public static function getCategoryIdFromPermissionEntityType(string $permissionEntityType): ?int
	{
		$dealCategoryId = \Bitrix\Crm\Category\DealCategory::convertFromPermissionEntityType($permissionEntityType);
		if($dealCategoryId !== -1)
		{
			return $dealCategoryId;
		}

		if (mb_strpos($permissionEntityType, \CCrmOwnerType::DynamicTypePrefixName) === 0)
		{
			[$prefix, $entityTypeId, $categoryId] = explode('_', $permissionEntityType);
			if ((string)$categoryId !== '')
			{
				$categoryId = (int)mb_substr($categoryId, 1);

				return $categoryId;
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

		if (!$result->hasAccess())
		{
			// access denied
			$expression = [0];
		}
		else
		{
			$expression = $result->getSqlExpression();
		}

		if (!is_array($filter))
		{
			$filter = [
				'@' . $primary => $expression,
			];
		}
		else
		{
			$filter = [
				$filter,
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
}
