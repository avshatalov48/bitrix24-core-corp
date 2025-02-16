<?php

namespace Bitrix\Crm\Service;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\EO_Status_Collection;
use Bitrix\Crm\Feature;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Security\AttributesProvider;
use Bitrix\Crm\Security\EntityPermission\ApproveCustomPermsToExistRole;
use Bitrix\Crm\Security\EntityPermission\MyCompany;
use Bitrix\Crm\Security\Manager;
use Bitrix\Crm\Security\QueryBuilder;
use Bitrix\Crm\Security\QueryBuilder\OptionsBuilder;
use Bitrix\Crm\Security\QueryBuilder\Result\JoinWithUnionSpecification;
use Bitrix\Crm\Security\QueryBuilder\Result\RawQueryObserverUnionResult;
use Bitrix\Crm\Security\QueryBuilderFactory;
use Bitrix\Crm\Security\Role\Manage\Entity\AutomatedSolutionConfig;
use Bitrix\Crm\Security\Role\Manage\Entity\AutomatedSolutionList;
use Bitrix\Crm\Security\Role\Manage\Entity\Button;
use Bitrix\Crm\Security\Role\Manage\Entity\ButtonConfig;
use Bitrix\Crm\Security\Role\Manage\Entity\WebForm;
use Bitrix\Crm\Security\Role\Manage\Entity\WebFormConfig;
use Bitrix\Crm\Security\Role\Manage\Permissions\MyCardView;
use Bitrix\Crm\Security\Role\Manage\Permissions\Transition;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\PermIdentifier;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use CCrmOwnerType;

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

	/**
	 * Is user a portal admin
	 *
	 * @return bool
	 */
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
				$groups = $currentUser::GetUserGroup($this->getUserId());
				$this->isAdmin = in_array(1, $groups, false);
			}
		}
		catch (\Exception $exception)
		{
		}

		return $this->isAdmin;
	}

	/**
	 * Is user a crm admin
	 *
	 * @return bool
	 */
	public function isCrmAdmin(): bool
	{
		return $this->canWriteConfig();
	}

	/**
	 * Is user an admin of automated solution
	 *
	 * @param int $automatedSolutionId
	 * @return bool
	 */
	public function isAutomatedSolutionAdmin(int $automatedSolutionId): bool
	{
		if (!Feature::enabled(Feature\PermissionsLayoutV2::class))
		{
			return $this->isCrmAdmin();
		}

		if ($this->isAdmin())
		{

			return true;
		}

		if ($this->canEditAutomatedSolutions())
		{
			return true;
		}

		return $this->getCrmPermissions()->havePerm(
			\Bitrix\Crm\Security\Role\Manage\Entity\AutomatedSolutionConfig::generateEntity($automatedSolutionId),
			static::PERMISSION_CONFIG,
			static::OPERATION_UPDATE
	);
	}

	/**
	 * Is user an admin of all automated solutions
	 *
	 * @return bool
	 */
	public function isAutomatedSolutionsAdmin(): bool
	{
		if (!Feature::enabled(Feature\PermissionsLayoutV2::class))
		{
			return $this->isCrmAdmin();
		}
		if ($this->isAdmin())
		{
			return true;
		}

		return $this->getCrmPermissions()->havePerm(
			\Bitrix\Crm\Security\Role\Manage\Entity\AutomatedSolutionList::ENTITY_CODE,
			static::PERMISSION_ALL,
			'CONFIG'
		);
	}

	/**
	 * Can user create, update or delete automated solutions
	 *
	 * @return bool
	 */
	public function canEditAutomatedSolutions(): bool
	{
		if (!Feature::enabled(Feature\PermissionsLayoutV2::class))
		{
			return $this->isCrmAdmin();
		}

		if ($this->isAdmin())
		{
			return true;
		}

		if ($this->isAutomatedSolutionsAdmin())
		{
			return true;
		}

		return $this->getCrmPermissions()->havePerm(
			\Bitrix\Crm\Security\Role\Manage\Entity\AutomatedSolutionList::ENTITY_CODE,
			static::PERMISSION_ALL,
			static::OPERATION_UPDATE
		);
	}

	/**
	 * Is user an admin of entity
	 *
	 * @param int $entityTypeId
	 * @return bool
	 */
	public function isAdminForEntity(int $entityTypeId): bool
	{
		if ($this->isAdmin())
		{
			return true;
		}
		if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			$automatedSolutionId = Container::getInstance()->getTypeByEntityTypeId($entityTypeId)?->getCustomSectionId();
			if ($automatedSolutionId)
			{
				return $this->isAutomatedSolutionAdmin($automatedSolutionId);
			}
		}

		return $this->isCrmAdmin();
	}

	public function canEditAutomation(int $entityTypeId, ?int $categoryId = null): bool
	{
		if ($this->isAdminForEntity($entityTypeId))
		{
			return true;
		}

		$categoryId = $categoryId ?? 0;
		$documentType = static::getPermissionEntityType($entityTypeId, $categoryId);

		return \CCrmAuthorizationHelper::CheckAutomationCreatePermission($documentType, $this->getCrmPermissions());
	}

	public function canWriteConfig(): bool
	{
		return $this->getCrmPermissions()->havePerm('CONFIG', static::PERMISSION_CONFIG, static::OPERATION_UPDATE);
	}

	public function canWriteWebFormConfig(): bool
	{
		return
			$this->isAdmin()
			|| $this->getCrmPermissions()->havePerm(WebFormConfig::CODE, BX_CRM_PERM_ALL, static::OPERATION_UPDATE)
		;
	}

	public function canWriteButtonConfig(): bool
	{
		return
			$this->isAdmin()
			|| $this->getCrmPermissions()->havePerm(ButtonConfig::CODE, BX_CRM_PERM_ALL, static::OPERATION_UPDATE)
		;
	}

	public function canUpdatePermission(PermIdentifier $permission): bool
	{
		$entity = $permission->entityCode;
		$buttonEntities = [
			Button::ENTITY_CODE,
			ButtonConfig::CODE,
		];
		$webFormEntities = [
			WebForm::ENTITY_CODE,
			WebFormConfig::CODE,
		];
		$automatedSolutionListEntities = [
			AutomatedSolutionList::ENTITY_CODE,
		];

		if (in_array($entity, $buttonEntities, true))
		{
			return $this->canWriteButtonConfig();
		}

		if (in_array($entity, $webFormEntities, true))
		{
			return $this->canWriteWebFormConfig();
		}

		if (in_array($entity, $automatedSolutionListEntities, true))
		{
			return $this->isAutomatedSolutionsAdmin();
		}

		if (str_starts_with($entity, AutomatedSolutionConfig::ENTITY_CODE_PREFIX))
		{
			$automatedSolutionId = AutomatedSolutionConfig::decodeAutomatedSolutionId($entity);
			if ($automatedSolutionId === null)
			{
				return false;
			}

			return $this->isAutomatedSolutionAdmin($automatedSolutionId);
		}

		$entityName = self::getEntityNameByPermissionEntityType($entity);
		$entityTypeId = CCrmOwnerType::ResolveID($entityName);

		return $this->isAdminForEntity($entityTypeId);
	}

	/**
	 * ATTENTION! Currently, user can't configure this permission.
	 * And because of hack in \CCrmPerms::HavePerm, this method returns TRUE for everyone.
	 *
	 * @return bool
	 */
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
	public function canAddType(?int $automatedSolutionId = null): bool
	{
		if ($automatedSolutionId)
		{
			return $this->isAutomatedSolutionAdmin($automatedSolutionId);
		}

		return $this->isCrmAdmin();
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

		return $this->isAdminForEntity($entityTypeId);
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
	public function getStageFieldName(int $entityTypeId): ?string
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);

		$stageFieldName = $factory?->getEntityFieldNameByMap(Item::FIELD_NAME_STAGE_ID);

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

	public function canAddItemsInCategory(Category $category): bool
	{
		return $this->checkAddPermissions($category->getEntityTypeId(), $category->getId());
	}

	public function canAddCategory(Category $category): bool
	{
		return $this->isCrmAdmin();
	}

	public function canUpdateCategory(Category $category): bool
	{
		return $this->isCrmAdmin();
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

	/**
	 * @return Category[]
	 */
	public function filterAvailableForAddingCategories(array $categories): array
	{
		return array_values(array_filter($categories, [$this, 'canAddItemsInCategory']));
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
			// $sections[0] - prefix
			// $sections[1] - entityTypeId
			// $sections[2] - categoryPostfix
			$sections = explode('_', $permissionEntityType);
			$entityTypeId = $sections[1] ?? 0;

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

	public function applyAvailableItemsGetListParameters(
		?array $parameters,
		array $permissionEntityTypes,
		?string $operation = self::OPERATION_READ,
		?string $primary = 'ID'
	): array
	{
		$optionsBuilder = new OptionsBuilder(
			new QueryBuilder\Result\RawQueryResult(
				identityColumnName: $primary
			)
		);
		$optionsBuilder->setSkipCheckOtherEntityTypes(!empty($permissionEntityTypes));

		if ($operation)
		{
			$optionsBuilder->setOperations((array)$operation);
		}

		$queryBuilder = $this->createListQueryBuilder($permissionEntityTypes, $optionsBuilder->build());
		$result = $queryBuilder->build();

		if (!$result->hasRestrictions())
		{
			// no need to apply filter
			return $parameters ?? [];
		}

		if (!$result->hasAccess())
		{
			$parameters['filter'] = $parameters['filter'] ?? [];
			$parameters['filter'] = [
				$parameters['filter'],
				'@' . $primary => [0],
			];

			return $parameters;
		}
		if ($result->isOrmConditionSupport())
		{
			$rf = new ReferenceField(
				'ENTITY',
				$result->getEntity(),
				$result->getOrmConditions(),
				['join_type' => 'INNER']
			);
			$currentRuntime = $parameters['runtime'] ?? [];

			$runtime = array_merge(
				['permissions' => $rf],
				$currentRuntime
			);

			$parameters = array_merge($parameters, ['runtime' => $runtime]);
		}
		else
		{
			$currentFilter = $parameters['filter'] ?? [];

			$parameters['filter'] = $this->addRestrictionFilter(
				$currentFilter,
				$primary,
				$result->getSqlExpression()
			);
		}

		return $parameters;
	}

	public function applyAvailableItemsFilter(
		?array $filter,
		array $permissionEntityTypes,
		?string $operation = self::OPERATION_READ,
		?string $primary = 'ID'
	): array
	{
		$queryResult =new QueryBuilder\Result\RawQueryResult(
			identityColumnName: $primary ?? 'ID'
		);

		if (JoinWithUnionSpecification::getInstance()->isSatisfiedBy($filter ?? []))
		{
			$queryResult = new RawQueryObserverUnionResult(identityColumnName: $primary ?? 'ID');
		}

		$filter = $filter ?? [];
		$optionsBuilder = new OptionsBuilder($queryResult);
		$optionsBuilder->setSkipCheckOtherEntityTypes(!empty($permissionEntityTypes));

		if ($operation)
		{
			$optionsBuilder->setOperations((array)$operation);
		}
		$queryBuilder = $this->createListQueryBuilder($permissionEntityTypes, $optionsBuilder->build());
		$result = $queryBuilder->build();

		if (!$result->hasRestrictions())
		{
			// no need to apply filter
			return $filter;
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

		return $this->addRestrictionFilter($filter, $primary, $expression);
	}

	private function addRestrictionFilter(array $filter, string $primary, $restrictExpression): array
	{
		if (empty($filter))
		{
			return ['@' . $primary => $restrictExpression];
		}

		if (array_key_exists('@' . $primary, $filter))
		{
			return [
				$filter,
				['@' . $primary => $restrictExpression]
			];
		}
		else
		{
			return array_merge(
				$filter,
				['@' . $primary => $restrictExpression]
			);
		}
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
		QueryBuilder\QueryBuilderOptions $options = null
	): QueryBuilder
	{
		$queryBuilderFactory = QueryBuilderFactory::getInstance();

		return $queryBuilderFactory->make((array)$permissionEntityTypes, $this, $options);
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

	public static function isPersonalViewAllowed(int $entityTypeId, ?int $categoryId): bool
	{
		$permission = new MyCardView();
		if ((new ApproveCustomPermsToExistRole())->hasWaitingPermission($permission))
		{
			return true;
		}

		if (self::isAlwaysAllowedEntity($entityTypeId))
		{
			return true;
		}

		$contactCategoryId = Container::getInstance()->getFactory(CCrmOwnerType::Contact)
			->getCategoryByCode('SMART_DOCUMENT_CONTACT')
			?->getId();

		if (CCrmOwnerType::Contact && $contactCategoryId === $categoryId)
		{
			return true;
		}

		$userId = Container::getInstance()->getContext()->getUserId();
		$userPermissions = \CCrmPerms::GetUserPermissions($userId);
		$entityName = static::getPermissionEntityType($entityTypeId, $categoryId);

		if (
			Container::getInstance()->getUserPermissions($userId)->isAdmin()
			|| Container::getInstance()->getUserPermissions($userId)->isCrmAdmin()
		)
		{
			return true;
		}

		return $userPermissions->GetPermType($entityName, $permission->code()) === \CCrmPerms::PERM_ALL;
	}

	private function getStageTransitions(int $entityTypeId, string $currentStage, ?int $categoryId): array
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
		if ($categoryId)
		{
			$entityTypeName = self::getPermissionEntityType($entityTypeId, $categoryId);
		}
		$stageFieldName = $this->getStageFieldName($entityTypeId);
		$permission = (new Transition())->code();
		$userPermissions = \CCrmRole::GetUserPerms($this->userId);
		$entityPermissions = $userPermissions['settings'][$entityTypeName][$permission]['-'] ?? [];

		$stageTransitions = [];
		if (isset($userPermissions['settings'][$entityTypeName][$permission][$stageFieldName][$currentStage]))
		{
			$stageTransitions = $userPermissions['settings'][$entityTypeName][$permission][$stageFieldName][$currentStage];
		}

		if (
			(
				(count($stageTransitions) === 1 && reset($stageTransitions) === Transition::TRANSITION_INHERIT)
				|| !$stageTransitions
			)
			&& $entityPermissions
		)
		{
			$stageTransitions = $entityPermissions;
		}

		return $stageTransitions;
	}

	public function isStageTransitionAllowed(string $currentStage, string $newStageId, ItemIdentifier $itemIdentifier): bool
	{
		if ((new ApproveCustomPermsToExistRole())->hasWaitingPermission(new Transition()))
		{
			return true;
		}

		if (self::isAlwaysAllowedEntity($itemIdentifier->getEntityTypeId()))
		{
			return true;
		}

		if ($this->isAdmin() || $this->isCrmAdmin())
		{
			return true;
		}

		if (!$this->checkUpdatePermissions($itemIdentifier->getEntityTypeId(), $itemIdentifier->getEntityId(), $itemIdentifier->getCategoryId()))
		{
			return false;
		}

		$transitions = $this->getStageTransitions($itemIdentifier->getEntityTypeId(), $currentStage, $itemIdentifier->getCategoryId());

		return in_array($newStageId, $transitions) || in_array(Transition::TRANSITION_ANY, $transitions);
	}

	//always allow for specific entities
	public static function isAlwaysAllowedEntity(int $entityTypeId): bool
	{
		return in_array($entityTypeId, [\CCrmOwnerType::SmartDocument, \CCrmOwnerType::SmartB2eDocument]);
	}

	public function canEditCopilotCallAssessmentSettings(): bool
	{
		if ($this->isCrmAdmin())
		{
			return true;
		}

		return $this->getCrmPermissions()->HavePerm(
			'CCA',
			BX_CRM_PERM_ALL,
			self::OPERATION_UPDATE
		);
	}

	public function canReadCopilotCallAssessmentSettings(): bool
	{
		if ($this->isCrmAdmin())
		{
			return true;
		}

		return $this->getCrmPermissions()->HavePerm(
			'CCA',
			BX_CRM_PERM_ALL,
			self::OPERATION_READ
		);
	}
}
