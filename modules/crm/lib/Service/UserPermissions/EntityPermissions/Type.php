<?php

namespace Bitrix\Crm\Service\UserPermissions\EntityPermissions;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Security\Role\PermissionsManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Settings\InvoiceSettings;

/**
 * @internal
 * Do not use directly, only through \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->entityType()
 */
class Type
{
	private array $canReadSomeItems = [];
	public function __construct(
		private readonly PermissionsManager $permissionsManager,
		private readonly CatalogEntityItem $catalogEntityItem,
		private readonly SaleEntityItem $saleEntityItem,
	)
	{
	}

	/**
	 * Check if user can view items.
	 * If entity support categories, we should check all categories of this type,
	 * and return true if user can view items in at least one of them.
	 *
	 * @param int $entityTypeId - Type identifier.
	 * @return bool
	 */
	public function canReadItems(int $entityTypeId): bool
	{
		if (CatalogEntityItem::isCatalogEntity($entityTypeId))
		{
			return $this->catalogEntityItem->canReadItems($this, $entityTypeId);
		}
		if (SaleEntityItem::isSaleEntity($entityTypeId))
		{
			return $this->saleEntityItem->canReadItems($this, $entityTypeId);
		}

		return $this->canDoOperation($entityTypeId, UserPermissions::OPERATION_READ);
	}

	public function canReadAllItemsOfType(int $entityTypeId, ?int $categoryId = null): bool
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);

		if (is_null($categoryId) && $factory?->isCategoriesSupported())
		{
			foreach ($factory->getCategories() as $category)
			{
				if (!$this->canReadAllItemsOfType($entityTypeId, $category->getId()))
				{
					return false;
				}
			}

			return true;
		}
		$permissionEntityType = (new PermissionEntityTypeHelper($entityTypeId))->getPermissionEntityTypeForCategory((int)$categoryId);

		return $this->permissionsManager
			->hasMaxPermissionLevel(
				$permissionEntityType,
				UserPermissions::OPERATION_READ
			)
		;
	}


	/**
	 * Returns true if user has permission to read at least one entity in CRM
	 * @return bool
	 */
	public function canReadSomeItemsInCrm(): bool
	{
		return $this->canReadSomeItems(0);
	}

	/**
	 * Returns true if user has permission to read at least one entity in CRM or has permission to read any smart process in automated solutions
	 * @return bool
	 */
	public function canReadSomeItemsInCrmOrAutomatedSolutions(): bool
	{
		return $this->canReadSomeItems(null);
	}

	/**
	 * Check if user can view items in the definite category.
	 *
	 * @param int $entityTypeId - Type identifier.
	 * @param int $categoryId Category identifier.
	 * @return bool
	 */
	public function canReadItemsInCategory(int $entityTypeId, int $categoryId): bool
	{
		if (CatalogEntityItem::isCatalogEntity($entityTypeId))
		{
			return $this->catalogEntityItem->canReadItems($this, $entityTypeId);
		}

		return $this->canDoOperationInCategory($entityTypeId, $categoryId, UserPermissions::OPERATION_READ);
	}

	/**
	 * Check if user can add items.
	 * If entity support categories, we should check all categories of this type,
	 * and return true if user can add items in at least one of them.
	 *
	 * @param int $entityTypeId - Type identifier.
	 * @return bool
	 */
	public function canAddItems(int $entityTypeId): bool
	{
		if (CatalogEntityItem::isCatalogEntity($entityTypeId))
		{
			return $this->catalogEntityItem->canAddItems($this, $entityTypeId);
		}
		if (SaleEntityItem::isSaleEntity($entityTypeId))
		{
			return $this->saleEntityItem->canAddItems($this, $entityTypeId);
		}

		if ($entityTypeId === \CCrmOwnerType::DealRecurring)
		{
			$entityTypeId = \CCrmOwnerType::Deal;
		}

		return $this->canDoOperation($entityTypeId, UserPermissions::OPERATION_ADD);
	}

	/**
	 * Check if user can add items in the definite category.
	 *
	 * @param int $entityTypeId - Type identifier.
	 * @param int $categoryId Category identifier.
	 * @return bool
	 */
	public function canAddItemsInCategory(int $entityTypeId, int $categoryId): bool
	{
		if (CatalogEntityItem::isCatalogEntity($entityTypeId))
		{
			return $this->catalogEntityItem->canAddItems($this, $entityTypeId);
		}
		if (SaleEntityItem::isSaleEntity($entityTypeId))
		{
			return $this->saleEntityItem->canAddItems($this, $entityTypeId);
		}


		return $this->canDoOperationInCategory($entityTypeId, $categoryId, UserPermissions::OPERATION_ADD);
	}

	/**
	 * Check if user can update items.
	 * If entity support categories, we should check all categories of this type,
	 * and return true if user can update items in at least one of them.
	 *
	 * @param int $entityTypeId - Type identifier.
	 * @return bool
	 */
	public function canUpdateItems(int $entityTypeId): bool
	{
		if (CatalogEntityItem::isCatalogEntity($entityTypeId))
		{
			return $this->catalogEntityItem->canUpdateItems($this, $entityTypeId);
		}
		if (SaleEntityItem::isSaleEntity($entityTypeId))
		{
			return $this->saleEntityItem->canUpdateItems($this, $entityTypeId);
		}

		return $this->canDoOperation($entityTypeId, UserPermissions::OPERATION_UPDATE);
	}

	/**
	 * Check if user can update items in the definite category.
	 *
	 * @param int $entityTypeId - Type identifier.
	 * @param int $categoryId Category identifier.
	 * @return bool
	 */
	public function canUpdateItemsInCategory(int $entityTypeId, int $categoryId): bool
	{
		if (CatalogEntityItem::isCatalogEntity($entityTypeId))
		{
			return $this->catalogEntityItem->canUpdateItems($this, $entityTypeId);
		}
		if (SaleEntityItem::isSaleEntity($entityTypeId))
		{
			return $this->saleEntityItem->canUpdateItems($this, $entityTypeId);
		}

		return $this->canDoOperationInCategory($entityTypeId, $categoryId, UserPermissions::OPERATION_UPDATE);
	}

	/**
	 * Check if user can delete items.
	 * If entity support categories, we should check all categories of this type,
	 * and return true if user can delete items in at least one of them.
	 *
	 * @param int $entityTypeId - Type identifier.
	 * @return bool
	 */
	public function canDeleteItems(int $entityTypeId): bool
	{
		if (CatalogEntityItem::isCatalogEntity($entityTypeId))
		{
			return $this->catalogEntityItem->canDeleteItems($this, $entityTypeId);
		}
		if (SaleEntityItem::isSaleEntity($entityTypeId))
		{
			return $this->saleEntityItem->canDeleteItems($this, $entityTypeId);
		}

		return $this->canDoOperation($entityTypeId, UserPermissions::OPERATION_DELETE);
	}

	/**
	 * Check if user can delete items in the definite category.
	 *
	 * @param int $entityTypeId - Type identifier.
	 * @param int $categoryId Category identifier.
	 * @return bool
	 */
	public function canDeleteItemsInCategory(int $entityTypeId, int $categoryId): bool
	{
		if (CatalogEntityItem::isCatalogEntity($entityTypeId))
		{
			return $this->catalogEntityItem->canDeleteItems($this, $entityTypeId);
		}
		if (SaleEntityItem::isSaleEntity($entityTypeId))
		{
			return $this->saleEntityItem->canDeleteItems($this, $entityTypeId);
		}

		return $this->canDoOperationInCategory($entityTypeId, $categoryId, UserPermissions::OPERATION_DELETE);
	}

	public function canExportItems(int $entityTypeId): bool
	{
		return $this->canDoOperation($entityTypeId, UserPermissions::OPERATION_EXPORT);
	}

	public function canExportItemsInCategory(int $entityTypeId, int $categoryId): bool
	{
		return $this->canDoOperationInCategory($entityTypeId, $categoryId, UserPermissions::OPERATION_EXPORT);
	}

	public function canImportItems(int $entityTypeId): bool
	{
		return $this->canDoOperation($entityTypeId, UserPermissions::OPERATION_IMPORT);
	}

	public function canImportItemsInCategory(int $entityTypeId, int $categoryId): bool
	{
		return $this->canDoOperationInCategory($entityTypeId, $categoryId, UserPermissions::OPERATION_IMPORT);
	}

	private function canDoOperation(int $entityTypeId, string $operation): bool
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);

		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId) && !$factory) // disable access for deleted smart-processes
		{
			return false;
		}

		$skipAllCategoriesCheckEntityType = [ // client permissions must check default category only
			\CCrmOwnerType::Contact,
			\CCrmOwnerType::Company,
		];

		if (
			!in_array($entityTypeId, $skipAllCategoriesCheckEntityType)
			&& $factory?->isCategoriesSupported()
		)
		{
			return $this->hasPermissionInAtLeastOneCategory($factory, $operation);
		}
		$entityName = (new PermissionEntityTypeHelper($entityTypeId))->getPermissionEntityTypeForCategory(0);

		return $this->permissionsManager->hasPermission($entityName, $operation);
	}

	private function canDoOperationInCategory(int $entityTypeId, int $categoryId, string $operation): bool
	{
		$entityName = (new PermissionEntityTypeHelper($entityTypeId))->getPermissionEntityTypeForCategory($categoryId);

		return $this->permissionsManager->hasPermission($entityName, $operation);
	}

	private function hasPermissionInAtLeastOneCategory(Factory $factory, string $operation): bool
	{
		foreach ($factory->getCategories() as $category)
		{
			$categoryEntityName = (new PermissionEntityTypeHelper($factory->getEntityTypeId()))->getPermissionEntityTypeForCategory($category->getId());
			if ($this->permissionsManager->hasPermission($categoryEntityName, $operation))
			{
				return true;
			}
		}

		return false;
	}

	private function canReadSomeItems(?int $automatedSolutionId): bool
	{

		$cacheKey = is_null($automatedSolutionId) ? -1 : $automatedSolutionId;
		if (isset($this->canReadSomeItems[$cacheKey]))
		{
			return $this->canReadSomeItems[$cacheKey];
		}

		$result =
			!$automatedSolutionId
			&& (
				$this->canReadItems(\CCrmOwnerType::Lead)
				|| $this->canReadItems(\CCrmOwnerType::Contact)
				|| $this->canReadItems(\CCrmOwnerType::Company)
				|| $this->canReadItems(\CCrmOwnerType::Deal)
				|| $this->canReadItems(\CCrmOwnerType::Quote)
			);
		if (!$automatedSolutionId && !$result && InvoiceSettings::getCurrent()->isOldInvoicesEnabled())
		{
			$result = $this->canReadItems(\CCrmOwnerType::Invoice);
		}
		if (!$automatedSolutionId && !$result && InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
		{
			$result = $this->canReadItems(\CCrmOwnerType::SmartInvoice);
		}

		if (!$result)
		{
			$dynamicTypesMap = Container::getInstance()->getDynamicTypesMap();
			// avoiding exceptions as this method has usages across the product.
			try
			{
				$dynamicTypesMap->load([
					'isLoadStages' => false,
					'isLoadCategories' => false,
				]);
			}
			catch (\Throwable $e)
			{
				\Bitrix\Crm\Service\Container::getInstance()->getLogger('Permissions')->critical(
					'canReadAnyItems: unable to load dynamic types map',
					[
						'code' => $e->getCode(),
						'message' => $e->getMessage(),
						'file' => $e->getFile(),
						'line' => $e->getLine(),
						'trace' => $e->getTraceAsString(),
					]
				);
			}
			foreach ($dynamicTypesMap->getTypes() as $type)
			{
				if (\CCrmOwnerType::isDynamicTypeBasedStaticEntity($type->getEntityTypeId()))
				{
					continue; // already was checked
				}
				if (
					(
						is_null($automatedSolutionId)
						|| $automatedSolutionId === (int)$type->getCustomSectionId()
					)
					&& $this->canReadItems($type->getEntityTypeId()))
				{
					$result = true;
					break;
				}
			}
		}
		$this->canReadSomeItems[$cacheKey] = $result;

		return $this->canReadSomeItems[$cacheKey];
	}
}
