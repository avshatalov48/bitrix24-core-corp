<?php

namespace Bitrix\Crm\Service\UserPermissions\EntityPermissions;

use Bitrix\Crm\Service\Container;

/**
 * @internal
 * Do not use directly, only through \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->category()
 */

class Category
{
	public function __construct(
		private readonly \Bitrix\Crm\Service\UserPermissions\EntityPermissions\Admin $entityAdmin,
		private readonly Type $entityType,
	)
	{
	}

	public function canAdd(\Bitrix\Crm\Category\Entity\Category $category): bool
	{
		return $this->entityAdmin->isAdminForEntity($category->getEntityTypeId(), $category->getId());
	}

	public function canUpdate(\Bitrix\Crm\Category\Entity\Category $category): bool
	{
		return $this->entityAdmin->isAdminForEntity($category->getEntityTypeId(), $category->getId());
	}

	public function canDelete(\Bitrix\Crm\Category\Entity\Category $category): bool
	{
		return $this->entityAdmin->isAdminForEntity($category->getEntityTypeId(), $category->getId());
	}

	public function canReadItems(\Bitrix\Crm\Category\Entity\Category $category): bool
	{
		return $this->entityType->canReadItemsInCategory($category->getEntityTypeId(), $category->getId());
	}

	public function canAddItems(\Bitrix\Crm\Category\Entity\Category $category): bool
	{
		return $this->entityType->canAddItemsInCategory($category->getEntityTypeId(), $category->getId());
	}

	public function canUpdateItems(\Bitrix\Crm\Category\Entity\Category $category): bool
	{
		return $this->entityType->canUpdateItemsInCategory($category->getEntityTypeId(), $category->getId());
	}

	/**
	 * @return int[]
	 */
	public function getAvailableForReadingCategoriesIds(int $entityTypeId): array
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory || !$factory->isCategoriesSupported())
		{
			return [];
		}

		return array_map(fn($category) => $category->getId(), $this->filterAvailableForReadingCategories($factory->getCategories()));
	}

	/**
	 * @return int[]
	 */
	public function getAvailableForAddingCategoriesIds(int $entityTypeId): array
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory || !$factory->isCategoriesSupported())
		{
			return [];
		}

		return array_map(fn($category) => $category->getId(), $this->filterAvailableForAddingCategories($factory->getCategories()));
	}

	/**
	 * @return int[]
	 */
	public function getAvailableForUpdatingCategoriesIds(int $entityTypeId): array
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory || !$factory->isCategoriesSupported())
		{
			return [];
		}

		return array_map(fn($category) => $category->getId(), $this->filterAvailableForUpdatingCategories($factory->getCategories()));
	}

	/**
	 * @return \Bitrix\Crm\Category\Entity\Category[]
	 */
	public function filterAvailableForAddingCategories(array $categories): array
	{
		return array_values(array_filter($categories, [$this, 'canAddItems']));
	}

	/**
	 * @return \Bitrix\Crm\Category\Entity\Category[]
	 */
	public function filterAvailableForReadingCategories(array $categories): array
	{
		return array_values(array_filter($categories, [$this, 'canReadItems']));
	}

	/**
	 * @return \Bitrix\Crm\Category\Entity\Category[]
	 */
	public function filterAvailableForUpdatingCategories(array $categories): array
	{
		return array_values(array_filter($categories, [$this, 'canUpdateItems']));
	}
}
