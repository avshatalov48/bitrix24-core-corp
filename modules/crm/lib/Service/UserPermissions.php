<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\EO_Status_Collection;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Security\AttributesProvider;
use Bitrix\Crm\Security\EntityPermission\MyCompany;
use Bitrix\Crm\Security\Role\Manage\Entity;
use Bitrix\Crm\Security\Role\PermissionsManager;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\PermIdentifier;
use Bitrix\Crm\Service\UserPermissions\Admin;
use Bitrix\Crm\Service\UserPermissions\AutomatedSolution;
use Bitrix\Crm\Service\UserPermissions\Automation;
use Bitrix\Crm\Service\UserPermissions\CopilotCallAssessment;
use Bitrix\Crm\Service\UserPermissions\DynamicType;
use Bitrix\Crm\Service\UserPermissions\EntityEditor;
use Bitrix\Crm\Service\UserPermissions\EntityPermissions\CatalogEntityItem;
use Bitrix\Crm\Service\UserPermissions\EntityPermissions\Category;
use Bitrix\Crm\Service\UserPermissions\EntityPermissions\Item;
use Bitrix\Crm\Service\UserPermissions\EntityPermissions\ItemsList;
use Bitrix\Crm\Service\UserPermissions\EntityPermissions\SaleEntityItem;
use Bitrix\Crm\Service\UserPermissions\EntityPermissions\Stage;
use Bitrix\Crm\Service\UserPermissions\EntityPermissions\Type;
use Bitrix\Crm\Service\UserPermissions\Exclusion;
use Bitrix\Crm\Service\UserPermissions\InventoryManagementContractor;
use Bitrix\Crm\Service\UserPermissions\Kanban;
use Bitrix\Crm\Service\UserPermissions\Permission;
use Bitrix\Crm\Service\UserPermissions\Product;
use Bitrix\Crm\Service\UserPermissions\RepeatSale;
use Bitrix\Crm\Service\UserPermissions\SaleTarget;
use Bitrix\Crm\Service\UserPermissions\SiteButton;
use Bitrix\Crm\Service\UserPermissions\WebForm;

class UserPermissions
{
	public const OPERATION_READ = 'READ';
	public const OPERATION_ADD = 'ADD';
	public const OPERATION_UPDATE = 'WRITE';
	public const OPERATION_DELETE = 'DELETE';
	public const OPERATION_EXPORT = 'EXPORT';
	public const OPERATION_IMPORT = 'IMPORT';

	public const PERMISSION_NONE = BX_CRM_PERM_NONE;
	public const PERMISSION_SELF = BX_CRM_PERM_SELF;
	public const PERMISSION_OPENED = BX_CRM_PERM_OPEN;
	public const PERMISSION_SUBDEPARTMENT = BX_CRM_PERM_SUBDEPARTMENT;
	public const PERMISSION_DEPARTMENT = BX_CRM_PERM_DEPARTMENT;
	public const PERMISSION_ALL = BX_CRM_PERM_ALL;
	public const PERMISSION_CONFIG = BX_CRM_PERM_CONFIG;

	public const ATTRIBUTES_OPENED = 'O';
	public const ATTRIBUTES_READ_ALL = 'RA';
	public const ATTRIBUTES_USER_PREFIX = 'U';
	public const ATTRIBUTES_CONCERNED_USER_PREFIX = 'CU';
	public const SETTINGS_INHERIT = 'INHERIT';

	protected int $userId;

	protected AttributesProvider $attributesProvider;
	protected ?MyCompany $myCompanyPermissions = null;
	protected ?Admin $adminPermissions = null;
	protected ?UserPermissions\EntityPermissions\Admin $entityAdminPermissions = null;
	protected ?Type $entityTypePermissions = null;
	protected ?Item $entityItemPermissions = null;
	protected ?ItemsList $entityItemFilterPermissions = null;
	protected ?Stage $entityStagePermissions = null;
	protected ?CatalogEntityItem $catalogEntityItemPermissions = null;
	protected ?SaleEntityItem $saleEntityItemPermissions = null;
	protected ?Product $productItemPermissions = null;
	protected ?AutomatedSolution $automatedSolutionPermissions = null;
	protected ?Automation $automationPermissions = null;
	protected ?DynamicType $dynamicTypePermissions = null;
	protected ?EntityEditor $entityEditorPermissions = null;
	protected ?Category $entityCategoryPermissions = null;
	protected ?Kanban $kanbanPermissions = null;
	protected ?Permission $permissionPermissions = null;
	protected ?WebForm $webFormPermissions = null;
	protected ?SiteButton $siteButtonPermissions = null;
	protected ?CopilotCallAssessment $copilotCallAssessmentPermissions = null;
	protected ?Exclusion $exclusionPermissions = null;
	protected ?SaleTarget $saleTargetPermissions = null;
	protected ?RepeatSale $repeatSalePermissions = null;
	protected ?InventoryManagementContractor $inventoryManagementContractorPermissions = null;

	/**
	 * @deprecated
	 * @var \CCrmPerms|null
	 * Please, don't use this property directly, as it can be null. Use the method instead
	 * @see UserPermissions::getCrmPermissions()
	 */
	protected $crmPermissions;

	public function __construct(int $userId)
	{
		$this->userId = $userId;
		$this->attributesProvider = new AttributesProvider($this->getUserId());
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	/**
	 * Check admin privileges
	 * @return Admin
	 */
	public function admin(): Admin
	{
		if (!$this->adminPermissions)
		{
			$this->adminPermissions = new Admin(
				$this->getUserId(),
				$this->getPermissionsManager()
			);
		}

		return $this->adminPermissions;
	}

	/**
	 * Check admin privileges
	 * @return UserPermissions\EntityPermissions\Admin
	 */
	public function entityAdmin(): UserPermissions\EntityPermissions\Admin
	{
		if (!$this->entityAdminPermissions)
		{
			$this->entityAdminPermissions = new UserPermissions\EntityPermissions\Admin(
				$this->admin(),
				$this->automatedSolution(),
				$this->inventoryManagementContractor(),
			);
		}

		return $this->entityAdminPermissions;
	}

	/**
	 * Manage permissions of an entity type
	 * @return Type
	 */
	public function entityType(): Type
	{
		if (!$this->entityTypePermissions)
		{
			$this->entityTypePermissions = new Type(
				$this->getPermissionsManager(),
				$this->catalogEntityItem(),
				$this->saleEntityItem()
			);
		}

		return $this->entityTypePermissions;
	}

	/**
	 * Manage permissions of an entity item
	 * @return Item
	 */
	public function item(): Item
	{
		if (!$this->entityItemPermissions)
		{
			$this->entityItemPermissions = new Item(
				$this->getPermissionsManager(),
				$this->admin(),
				$this->entityAdmin(),
				$this->entityType(),
				$this->catalogEntityItem(),
				$this->saleEntityItem()
			);
		}

		return $this->entityItemPermissions;
	}

	/**
	 * Manage permissions of entity items in getList-like operations
	 * @return ItemsList
	 */
	public function itemsList(): ItemsList
	{
		if (!$this->entityItemFilterPermissions)
		{
			$this->entityItemFilterPermissions = new ItemsList($this);
		}

		return $this->entityItemFilterPermissions;
	}

	/**
	 * Check permissions of stages of an entity type
	 * @return Stage
	 */
	public function stage(): Stage
	{
		if (!$this->entityStagePermissions)
		{
			$this->entityStagePermissions = new Stage(
				$this->getPermissionsManager(),
			);
		}

		return $this->entityStagePermissions;
	}

	/**
	 * Manage permissions of catalog products
	 * @return Product
	 */
	public function product(): Product
	{
		if (!$this->productItemPermissions)
		{
			$this->productItemPermissions = new Product(
				$this->admin(),
				$this->entityType(),
			);
		}

		return $this->productItemPermissions;
	}

	/**
	 * Manage permissions of an automated solution
	 * @return AutomatedSolution
	 */
	public function automatedSolution(): AutomatedSolution
	{
		if (!$this->automatedSolutionPermissions)
		{
			$this->automatedSolutionPermissions = new AutomatedSolution(
				$this->getPermissionsManager(),
				$this->admin(),
			);
		}

		return $this->automatedSolutionPermissions;
	}

	/**
	 * Manage permissions for business processes and automation
	 * @return Automation
	 */
	public function automation(): Automation
	{
		if (!$this->automationPermissions)
		{
			$this->automationPermissions = new Automation(
				$this->getPermissionsManager(),
				$this->entityAdmin(),
			);
		}

		return $this->automationPermissions;
	}

	/**
	 * Manage permissions for dynamic entity types
	 * @return DynamicType
	 */
	public function dynamicType(): DynamicType
	{
		if (!$this->dynamicTypePermissions)
		{
			$this->dynamicTypePermissions = new DynamicType(
				$this->automatedSolution(),
				$this->admin(),
				$this->entityAdmin(),
			);
		}

		return $this->dynamicTypePermissions;
	}

	/**
	 * Manage permissions for entity editor
	 * @return EntityEditor
	 */
	public function entityEditor(): EntityEditor
	{
		if (!$this->entityEditorPermissions)
		{
			$this->entityEditorPermissions = new EntityEditor(
				$this->getUserId(),
				$this->getPermissionsManager(),
				$this->admin(),
				$this->entityAdmin(),
			);
		}

		return $this->entityEditorPermissions;
	}

	/**
	 * Manage permissions for entity categories
	 * @return Category
	 */
	public function category(): Category
	{
		if (!$this->entityCategoryPermissions)
		{
			$this->entityCategoryPermissions = new Category(
				$this->entityAdmin(),
				$this->entityType(),
			);
		}

		return $this->entityCategoryPermissions;
	}

	/**
	 * Manage permissions for entity categories
	 * @return Kanban
	 */
	public function kanban(): Kanban
	{
		if (!$this->kanbanPermissions)
		{
			$this->kanbanPermissions = new Kanban(
				$this->getPermissionsManager(),
				$this->entityAdmin(),
			);
		}

		return $this->kanbanPermissions;
	}

	/**
	 * Manage permissions for permissions
	 * @return Permission
	 */
	public function permission(): Permission
	{
		if (!$this->permissionPermissions)
		{
			$this->permissionPermissions = new Permission(
				$this->admin(),
				$this->entityAdmin(),
				$this->siteButton(),
				$this->webForm(),
				$this->automatedSolution(),
				$this->inventoryManagementContractor(),
			);
		}

		return $this->permissionPermissions;
	}

	/**
	 * Manage permissions for web forms
	 * @return WebForm
	 */
	public function webForm(): WebForm
	{
		if (!$this->webFormPermissions)
		{
			$this->webFormPermissions = new WebForm(
				$this->getPermissionsManager(),
			);
		}

		return $this->webFormPermissions;
	}

	/**
	 * Manage permissions for site button
	 * @return SiteButton
	 */
	public function siteButton(): SiteButton
	{
		if (!$this->siteButtonPermissions)
		{
			$this->siteButtonPermissions = new SiteButton(
				$this->getPermissionsManager(),
			);
		}

		return $this->siteButtonPermissions;
	}

	/**
	 * Manage permissions for copilot call assessment (AI Speech analytics and Sales scripts)
	 * @return CopilotCallAssessment
	 */
	public function copilotCallAssessment(): CopilotCallAssessment
	{
		if (!$this->copilotCallAssessmentPermissions)
		{
			$this->copilotCallAssessmentPermissions = new CopilotCallAssessment(
				$this->getPermissionsManager(),
				$this->admin(),
			);
		}

		return $this->copilotCallAssessmentPermissions;
	}

	/**
	 * Manage permissions for repeat sales
	 * @return RepeatSale
	 */
	public function repeatSale(): RepeatSale
	{
		if (!$this->repeatSalePermissions)
		{
			$this->repeatSalePermissions = new RepeatSale(
				$this->getPermissionsManager(),
				$this->admin(),
			);
		}

		return $this->repeatSalePermissions;
	}

	/**
	 * Manage permissions for exclusion list
	 * @return Exclusion
	 */
	public function exclusion(): Exclusion
	{
		if (!$this->exclusionPermissions)
		{
			$this->exclusionPermissions = new Exclusion(
				$this->getPermissionsManager()
			);
		}

		return $this->exclusionPermissions;
	}

	/**
	 * Manage permissions for sale target
	 * @return SaleTarget
	 */
	public function saleTarget(): SaleTarget
	{
		if (!$this->saleTargetPermissions)
		{
			$this->saleTargetPermissions = new SaleTarget(
				$this->getUserId(),
				$this->getPermissionsManager(),
			);
		}

		return $this->saleTargetPermissions;
	}

	public function inventoryManagementContractor(): InventoryManagementContractor
	{
		if (!$this->inventoryManagementContractorPermissions)
		{
			$this->inventoryManagementContractorPermissions = new InventoryManagementContractor(
				$this->getPermissionsManager(),
			);
		}

		return $this->inventoryManagementContractorPermissions;
	}

	public function getAttributesProvider(): AttributesProvider
	{
		return $this->attributesProvider;
	}

	public function myCompany(): MyCompany
	{
		if (!$this->myCompanyPermissions)
		{
			$this->myCompanyPermissions = new MyCompany($this);
		}

		return $this->myCompanyPermissions;
	}

	/**
	 * Is user a portal admin
	 *
	 * @return bool
	 */
	public function isAdmin(): bool
	{
		return $this->admin()->isAdmin();
	}

	/**
	 * Is user a crm admin
	 *
	 * @return bool
	 */
	public function isCrmAdmin(): bool
	{
		return $this->admin()->isCrmAdmin();
	}

	/**
	 * Is user an admin of entity
	 *
	 * @param int $entityTypeId
	 * @param int|null $categoryId
	 * @return bool
	 */
	public function isAdminForEntity(int $entityTypeId, ?int $categoryId = null): bool
	{
		return $this->entityAdmin()->isAdminForEntity($entityTypeId, $categoryId);
	}

	private function getPermissionsManager(): PermissionsManager
	{
		return PermissionsManager::getInstance($this->getUserId());
	}

	private function catalogEntityItem(): CatalogEntityItem
	{
		if (!$this->catalogEntityItemPermissions)
		{
			$this->catalogEntityItemPermissions = new CatalogEntityItem(
				$this->getUserId(),
			);
		}

		return $this->catalogEntityItemPermissions;
	}

	private function saleEntityItem(): SaleEntityItem
	{
		if (!$this->saleEntityItemPermissions)
		{
			$this->saleEntityItemPermissions = new SaleEntityItem();
		}

		return $this->saleEntityItemPermissions;
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->isCrmAdmin()
	 * Method will be removed after March 2025
	 *
	 * @return bool
	 */
	public function canWriteConfig(): bool
	{
		return $this->admin()->isCrmAdmin();
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->automatedSolution()->canEdit()
	 * Method will be removed after March 2025
	 *
	 * @return bool
	 */
	public function canEditAutomatedSolutions(): bool
	{
		return $this->automatedSolution()->canEdit();
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->entityType()->canReadItems
	 */
	public function canReadType(int $entityTypeId): bool
	{
		return $this->entityType()->canReadItems($entityTypeId);
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->dynamicType()->canUpdate
	 * Method will be removed after March 2025
	 */
	public function canUpdateType(int $entityTypeId): bool
	{
		return $this->dynamicType()->canUpdate($entityTypeId);
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->entityType()->canReadItemsInCategory
	 */
	public function canReadTypeInCategory(int $entityTypeId, int $categoryId): bool
	{
		return $this->entityType()->canReadItemsInCategory($entityTypeId, $categoryId);
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->item()->canAdd to check add permissions for definite stageId
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->entityType()->canAddItems to check if user has access to add something of this EntityType
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->entityType()->canAddItemsInCategory to check if user has access to add something of this EntityType in $categoryId
	 *
	 * Method will be removed after March 2025
	 */
	public function checkAddPermissions(int $entityTypeId, ?int $categoryId = null, ?string $stageId = null): bool
	{
		if (is_null($stageId))
		{
			return is_null($categoryId)
				? $this->entityType()->canAddItems($entityTypeId)
				: $this->entityType()->canAddItemsInCategory($entityTypeId, $categoryId)
			;
		}

		return $this->stage()->canAddInStage($entityTypeId, $categoryId, $stageId);
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->item()->canUpdate to check read permissions for definite item
	 *
	 * Method will be removed after March 2025
	 */
	public function checkUpdatePermissions(int $entityTypeId, int $id, ?int $categoryId = null): bool
	{
		if ($id <= 0)
		{
			return is_null($categoryId)
				? $this->entityType()->canUpdateItems($entityTypeId)
				: $this->entityType()->canUpdateItemsInCategory($entityTypeId, $categoryId)
			;
		}

		$itemId = ItemIdentifier::createByParams($entityTypeId, $id, $categoryId);

		return $itemId && $this->item()->canUpdateItemIdentifier($itemId);
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->item()->canUpdateItem
	 *
	 * Method will be removed after March 2025
	 */
	public function canUpdateItem(\Bitrix\Crm\Item $item): bool
	{
		return $this->item()->canUpdateItem($item);
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->item()->canDelete to check delete permissions for definite stageId
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->entityType()->canDeleteItems to check if user has access to delete something of this EntityType
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->entityType()->canDeleteItemsInCategory to check if user has access to delete something of this EntityType in $categoryId
	 *
	 * Method will be removed after March 2025
	 */
	public function checkDeletePermissions(int $entityTypeId, int $id = 0, ?int $categoryId = null): bool
	{
		if ($id <= 0)
		{
			return is_null($categoryId)
				? $this->entityType()->canDeleteItems($entityTypeId)
				: $this->entityType()->canDeleteItemsInCategory($entityTypeId, $categoryId)
			;
		}
		$itemId = ItemIdentifier::createByParams($entityTypeId, $id, $categoryId);

		return $itemId && $this->item()->canDeleteItemIdentifier($itemId);
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->item()->canRead to check read permissions for definite item
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->entityType()->canReadItems to check if user has access to read something of this EntityType
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->entityType()->canReadItemsInCategory to check if user has access to read something of this EntityType in $categoryId
	 *
	 * Method will be removed after March 2025
	 */
	public function checkReadPermissions(int $entityTypeId, int $id = 0, ?int $categoryId = null): bool
	{
		if ($id <= 0)
		{
			return is_null($categoryId)
				? $this->entityType()->canReadItems($entityTypeId)
				: $this->entityType()->canReadItemsInCategory($entityTypeId, $categoryId)
			;
		}
		$itemId = ItemIdentifier::createByParams($entityTypeId, $id, $categoryId);

		return $itemId && $this->item()->canReadItemIdentifier($itemId);
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->item()->canReadItem
	 *
	 * Method will be removed after March 2025
	 */
	public function canReadItem(\Bitrix\Crm\Item $item): bool
	{
		return $this->item()->canReadItem($item);
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->category()->canReadItems
	 *
	 * Method will be removed after March 2025
	 */
	public function canViewItemsInCategory(\Bitrix\Crm\Category\Entity\Category $category): bool
	{
		return $this->category()->canReadItems($category);
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->category()->canAdd
	 *
	 * Method will be removed after March 2025
	 */
	public function canAddCategory(\Bitrix\Crm\Category\Entity\Category $category): bool
	{
		return $this->category()->canAdd($category);
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->category()->canUpdate
	 *
	 * Method will be removed after March 2025
	 */
	public function canUpdateCategory(\Bitrix\Crm\Category\Entity\Category $category): bool
	{
		return $this->category()->canUpdate($category);
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->category()->canDelete
	 *
	 * Method will be removed after March 2025
	 */
	public function canDeleteCategory(\Bitrix\Crm\Category\Entity\Category $category): bool
	{
		return $this->category()->canDelete($category);
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->category()->filterAvailableForReadingCategories
	 *
	 * Method will be removed after March 2025
	 */
	public function filterAvailableForReadingCategories(array $categories): array
	{
		return $this->category()->filterAvailableForReadingCategories($categories);
	}

	/**
	 * @deprecated
	 * @see (new \Bitrix\Crm\Category\PermissionEntityTypeHelper($entityTypeId))->getPermissionEntityTypeForCategory($categoryId)
	 *
	 * Method will be removed after March 2025
	 */
	public static function getPermissionEntityType(int $entityTypeId, int $categoryId = 0): string
	{
		return (new PermissionEntityTypeHelper($entityTypeId))->getPermissionEntityTypeForCategory($categoryId);
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->stage()->getFirstAvailableForAddStageId()
	 * Method will be removed after March 2025
	 */
	public function getStartStageId(
		int $entityTypeId,
		EO_Status_Collection $stages,
		int $categoryId = 0,
		string $operation = self::OPERATION_ADD
	): ?string
	{
		return $this->stage()->getFirstAvailableForAddStageId($entityTypeId, $categoryId, $stages);
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->myCompany()
	 * Method will be removed after March 2025
	 *
	 * @return bool
	 */
	public function getMyCompanyPermissions(): MyCompany
	{
		return $this->myCompany();
	}

	/**
	 * @deprecated CCrmPerms is deprecated
	 */
	public function getCrmPermissions(): \CCrmPerms
	{
		if (!$this->crmPermissions)
		{
			$this->crmPermissions = new \CCrmPerms($this->userId);
		}

		return $this->crmPermissions;
	}
}
