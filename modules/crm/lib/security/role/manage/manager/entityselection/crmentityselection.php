<?php

namespace Bitrix\Crm\Security\Role\Manage\Manager\EntitySelection;

use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Security\Role\Manage\Enum\Permission;
use Bitrix\Crm\Security\Role\Manage\Manager\EntitySelection;
use Bitrix\Crm\Security\Role\Manage\PermissionEntityBuilder;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Web\Uri;
use CCrmOwnerType;

class CrmEntitySelection extends EntitySelection
{
	public function getUrl(): ?Uri
	{
		$criterion = $this->buildCriterion();

		return new Uri("/crm/perms/{$criterion}/");
	}

	public function buildModels(): array
	{
		$permissionCase = Permission::fromEntityTypeId($this->entityTypeId);
		if ($permissionCase === null)
		{
			return [];
		}

		return (new PermissionEntityBuilder())
			->include($permissionCase)
			->filterByCategory(Permission::Deal, $this->categoryId)
			->filterByEntityTypeIds(Permission::Dynamic, [ $this->entityTypeId ])
			->filterByCategory(Permission::Dynamic, $this->categoryId)
			->filterByCategory(Permission::Contact, $this->categoryId ? : 0)
			->filterByCategory(Permission::Company, $this->categoryId ? : 0)
			->buildOfMade()
		;
	}

	public static function isSuitableEntity(int $entityTypeId, ?int $categoryId = null): bool
	{
		$entityTypeIds = [
			CCrmOwnerType::Deal,
			CCrmOwnerType::Lead,
			CCrmOwnerType::Contact,
			CCrmOwnerType::Company,
			CCrmOwnerType::Quote,
			CCrmOwnerType::SmartInvoice,
		];

		$isCompanyOrContact = in_array($entityTypeId, [CCrmOwnerType::Contact, CCrmOwnerType::Company], true);
		$isDefaultCategory = ($categoryId ?? 0) === 0;
		if ($isCompanyOrContact && !$isDefaultCategory)
		{
			return false;
		}

		return
			in_array($entityTypeId, $entityTypeIds, true)
			|| self::isSuitableDynamicEntity($entityTypeId)
		;
	}

	private static function isSuitableDynamicEntity(int $entityTypeId): bool
	{
		return
			!CCrmOwnerType::isDynamicTypeBasedStaticEntity($entityTypeId)
			&& CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
			&& CCrmOwnerType::IsDefined($entityTypeId)
			&& !IntranetManager::isEntityTypeInCustomSection($entityTypeId)
		;
	}

	public function isAvailableTool(): bool
	{
		return Container::getInstance()
			->getIntranetToolsManager()
			->checkEntityTypeAvailability($this->entityTypeId)
		;
	}

	public function printInaccessibilityContent(): void
	{
		$typeIds = [
			CCrmOwnerType::SmartInvoice,
			CCrmOwnerType::Quote,
		];

		if (
			in_array($this->entityTypeId, $typeIds, true)
			|| CCrmOwnerType::isPossibleDynamicTypeId($this->entityTypeId)
		)
		{
			print AvailabilityManager::getInstance()
				->getEntityTypeInaccessibilityContent($this->entityTypeId)
			;

			return;
		}

		print AvailabilityManager::getInstance()->getCrmInaccessibilityContent();
	}
}
