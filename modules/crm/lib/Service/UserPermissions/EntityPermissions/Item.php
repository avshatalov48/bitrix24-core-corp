<?php

namespace Bitrix\Crm\Service\UserPermissions\EntityPermissions;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Security\EntityPermission\ApproveCustomPermsToExistRole;
use Bitrix\Crm\Security\Role\Manage\Permissions\Transition;
use Bitrix\Crm\Security\Role\PermissionsManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Service\UserPermissions\Admin;
use Bitrix\Crm\Service\UserPermissions\Helper\Stage;

/**
 * @internal
 * Do not use directly, only through \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->item()
 */

class Item
{
	public function __construct(
		private readonly PermissionsManager $permissionsManager,
		private readonly Admin $admin,
		private readonly UserPermissions\EntityPermissions\Admin $entityAdmin,
		private readonly Type $entityType,
		private readonly CatalogEntityItem $catalogEntityItem,
		private readonly SaleEntityItem $saleEntityItem,
	)
	{
	}

	/**
	 * Returns true if user has permission to read $entityId of $entityTypeId.
	 * @param int $entityTypeId
	 * @param int $entityId
	 * @return bool
	 */
	public function canRead(int $entityTypeId, int $entityId): bool
	{
		$itemIdentifier = ItemIdentifier::createByParams($entityTypeId, $entityId);

		return $itemIdentifier && $this->canReadItemIdentifier($itemIdentifier);
	}

	/**
	 * Returns true if user has permission to update $entityId of $entityTypeId.
	 * @param int $entityTypeId
	 * @param int $entityId
	 * @return bool
	 */
	public function canUpdate(int $entityTypeId, int $entityId): bool
	{
		$itemIdentifier = ItemIdentifier::createByParams($entityTypeId, $entityId);

		return $itemIdentifier && $this->canUpdateItemIdentifier($itemIdentifier);
	}

	/**
	 * Returns true if user has permission to delete $entityId of $entityTypeId.
	 * @param int $entityTypeId
	 * @param int $entityId
	 * @return bool
	 */
	public function canDelete(int $entityTypeId, int $entityId): bool
	{
		$itemIdentifier = ItemIdentifier::createByParams($entityTypeId, $entityId);

		return $itemIdentifier && $this->canDeleteItemIdentifier($itemIdentifier);
	}

	/**
	 * Returns true if user has permission to read $itemIdentifier.
	 * @param ItemIdentifier $itemIdentifier
	 * @return bool
	 */
	public function canReadItemIdentifier(ItemIdentifier $itemIdentifier): bool
	{
		if (CatalogEntityItem::isCatalogEntity($itemIdentifier->getEntityTypeId()))
		{
			return $this->catalogEntityItem->canRead($this, $itemIdentifier->getEntityTypeId(), $itemIdentifier->getEntityId());
		}
		if (SaleEntityItem::isSaleEntity($itemIdentifier->getEntityTypeId()))
		{
			return $this->saleEntityItem->canRead($this, $this->entityType, $itemIdentifier->getEntityTypeId(), $itemIdentifier->getEntityId());
		}

		return $this->hasPermissions($itemIdentifier, UserPermissions::OPERATION_READ);
	}

	/**
	 * Returns true if user has permission to update $itemIdentifier.
	 * @param ItemIdentifier $itemIdentifier
	 * @return bool
	 */
	public function canUpdateItemIdentifier(ItemIdentifier $itemIdentifier): bool
	{
		if (CatalogEntityItem::isCatalogEntity($itemIdentifier->getEntityTypeId()))
		{
			return $this->catalogEntityItem->canUpdate($this, $itemIdentifier->getEntityTypeId(), $itemIdentifier->getEntityId());
		}
		if (SaleEntityItem::isSaleEntity($itemIdentifier->getEntityTypeId()))
		{
			return $this->saleEntityItem->canUpdate($this, $this->entityType, $itemIdentifier->getEntityTypeId(), $itemIdentifier->getEntityId());
		}

		return $this->hasPermissions($itemIdentifier, UserPermissions::OPERATION_UPDATE);
	}

	/**
	 * Returns true if user has permission to delete $itemIdentifier.
	 * @param ItemIdentifier $itemIdentifier
	 * @return bool
	 */
	public function canDeleteItemIdentifier(ItemIdentifier $itemIdentifier): bool
	{
		if (CatalogEntityItem::isCatalogEntity($itemIdentifier->getEntityTypeId()))
		{
			return $this->catalogEntityItem->canDelete($this, $itemIdentifier->getEntityTypeId(), $itemIdentifier->getEntityId());
		}
		if (SaleEntityItem::isSaleEntity($itemIdentifier->getEntityTypeId()))
		{
			return $this->saleEntityItem->canDelete($this, $this->entityType, $itemIdentifier->getEntityTypeId(), $itemIdentifier->getEntityId());
		}

		return $this->hasPermissions($itemIdentifier, UserPermissions::OPERATION_DELETE);
	}

	public function canReadItem(\Bitrix\Crm\Item $item): bool
	{
		if ($item->isNew())
		{
			return $this->entityType->canReadItemsInCategory($item->getEntityTypeId(), (int)$item->getCategoryIdForPermissions());
		}
		$itemId = ItemIdentifier::createByParams($item->getEntityTypeId(), $item->getId(), $item->getCategoryIdForPermissions());

		return $itemId && $this->canReadItemIdentifier($itemId);
	}

	public function canAddItem(\Bitrix\Crm\Item $item): bool
	{
		return $this->checkItemPermissions($item, UserPermissions::OPERATION_ADD);
	}

	public function canUpdateItem(\Bitrix\Crm\Item $item): bool
	{
		$itemId = ItemIdentifier::createByParams($item->getEntityTypeId(), $item->getId(), $item->getCategoryIdForPermissions());

		return $itemId && $this->canUpdateItemIdentifier($itemId);
	}

	public function canDeleteItem(\Bitrix\Crm\Item $item): bool
	{
		$itemId = ItemIdentifier::createByParams($item->getEntityTypeId(), $item->getId(), $item->getCategoryIdForPermissions());

		return $itemId && $this->canDeleteItemIdentifier($itemId);
	}

	public function canImportItem(\Bitrix\Crm\Item $item): bool
	{
		return $this->checkItemPermissions($item, UserPermissions::OPERATION_IMPORT);
	}

	public function canChangeStage(ItemIdentifier $itemIdentifier, string $fromStageId, string $toStageId): bool
	{
		if ((new ApproveCustomPermsToExistRole())->hasWaitingPermission(new Transition()))
		{
			return true;
		}

		$factory = Container::getInstance()->getFactory($itemIdentifier->getEntityTypeId());
		if (!$factory || $factory->hasCustomPermissionsUI())
		{
			return true;
		}

		if ($this->entityAdmin->isAdminForEntity($itemIdentifier->getEntityTypeId()))
		{
			return true;
		}

		if (!$this->canUpdateItemIdentifier($itemIdentifier))
		{
			return false;
		}

		$permissionEntity = \Bitrix\Crm\Category\PermissionEntityTypeHelper::getPermissionEntityTypeForItemIdentifier($itemIdentifier);
		$permissionType = (new Transition())->code();

		$allowedStagesIds = $this->permissionsManager
			->getPermissionLevel($permissionEntity, $permissionType)
			->getSettingsForStage($fromStageId)
		;

		return in_array($toStageId, $allowedStagesIds) || in_array(Transition::TRANSITION_ANY, $allowedStagesIds);
	}

	public function canChangeStageToAny(int $entityTypeId): bool
	{
		$isEntityAdmin = $this->entityAdmin->isAdminForEntity($entityTypeId);
		$factory = Container::getInstance()->getFactory($entityTypeId);

		return $isEntityAdmin || !$factory || $factory->hasCustomPermissionsUI();
	}

	public function canAddOnlySelfAssignedItems(\Bitrix\Crm\Item $item): bool
	{
		$itemPermissionAttributes = $this->prepareItemPermissionAttributes($item);

		return $this->permissionsManager
			->getPermissionLevel(PermissionEntityTypeHelper::getPermissionEntityTypeForItem($item), UserPermissions::OPERATION_ADD)
			->isPermissionLevelEqualsToByEntityAttributes(UserPermissions::PERMISSION_SELF, $itemPermissionAttributes)
		;
	}

	public function prepareItemPermissionAttributes(\Bitrix\Crm\Item $item): array
	{
		// todo process multiple assigned
		$assignedById = $item->getAssignedById();
		$attributes = [UserPermissions::ATTRIBUTES_USER_PREFIX . $assignedById];
		if ($item->getOpened())
		{
			$attributes[] = UserPermissions::ATTRIBUTES_OPENED;
		}

		$stageFieldName = $item->getEntityFieldNameIfExists(\Bitrix\Crm\Item::FIELD_NAME_STAGE_ID);
		if ($stageFieldName)
		{
			$stageId = $item->getStageId();
			if ($stageId)
			{
				$attributes[] = Stage::combineStageIdAttribute($stageFieldName, $item->getStageId());
			}
		}

		if ($item->hasField(\Bitrix\Crm\Item::FIELD_NAME_OBSERVERS))
		{
			foreach ($item->getObservers() as $observerId)
			{
				$attributes[] = UserPermissions::ATTRIBUTES_CONCERNED_USER_PREFIX . $observerId;
			}
		}

		if ($item->hasField(\Bitrix\Crm\Item\Company::FIELD_NAME_IS_MY_COMPANY) && $item->get(\Bitrix\Crm\Item\Company::FIELD_NAME_IS_MY_COMPANY))
		{
			$attributes[] = UserPermissions::ATTRIBUTES_READ_ALL;
		}

		$attributesProvider = Container::getInstance()->getUserPermissions((int)$assignedById)->getAttributesProvider();
		$userAttributes = $attributesProvider->getEntityAttributes();

		return array_merge($attributes, $userAttributes['INTRANET']);
	}

	/**
	 * Preload data for permission attributes to increase performance in group mode permission checks
	 *
	 * @param int $entityTypeId
	 * @param int[] $ids
	 * @return void
	 */
	public function preloadPermissionAttributes(int $entityTypeId, array $ids): void
	{
		if (empty($ids))
		{
			return;
		}
		$permissionEntity = (new PermissionEntityTypeHelper($entityTypeId))->getPermissionEntityTypeForCategory(0);

		\Bitrix\Crm\Security\Manager::resolveController($permissionEntity)->getPermissionAttributes($permissionEntity, $ids); // just preload
	}

	private function hasPermissions(ItemIdentifier $itemIdentifier, string $permissionType): bool
	{
		if (
			\CCrmOwnerType::isUseDynamicTypeBasedApproach($itemIdentifier->getEntityTypeId())
			&& !Container::getInstance()->getFactory($itemIdentifier->getEntityTypeId()) // disable access for deleted smart-processes
		)
		{
			return false;
		}

		if ($this->admin->isAdmin())
		{
			return true;
		}

		if ($itemIdentifier->getEntityTypeId() === \CCrmOwnerType::DealRecurring) // recurring deal permissions should be checked for real deals
		{
			$itemIdentifier = new ItemIdentifier(\CCrmOwnerType::Deal, $itemIdentifier->getEntityId(), $itemIdentifier->getCategoryId());
		}

		$permissionEntity = \Bitrix\Crm\Category\PermissionEntityTypeHelper::getPermissionEntityTypeForItemIdentifier($itemIdentifier);
		$entityId = $itemIdentifier->getEntityId();

		$securityController = \Bitrix\Crm\Security\Manager::resolveController($permissionEntity);
		if (!$securityController)
		{
			return false;
		}

		$entityAttrs = $securityController->getPermissionAttributes($permissionEntity, [$entityId]);
		$entityAttrs = $entityAttrs[$entityId] ?? [];

		return
			$this->permissionsManager->hasPermission($permissionEntity, $permissionType)
			&& $this->permissionsManager->doUserAttributesMatchesToEntityAttributes($permissionEntity, $permissionType, $entityAttrs)
		;
	}

	private function checkItemPermissions(\Bitrix\Crm\Item $item, string $permissionType): bool
	{
		$itemPermissionAttributes = $this->prepareItemPermissionAttributes($item);

		return $this->permissionsManager
			->hasPermissionByEntityAttributes(PermissionEntityTypeHelper::getPermissionEntityTypeForItem($item), $permissionType, $itemPermissionAttributes)
		;
	}
}
