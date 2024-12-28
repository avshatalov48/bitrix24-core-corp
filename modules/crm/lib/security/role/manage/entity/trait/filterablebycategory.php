<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity\Trait;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Model\EO_ItemCategory;
use Bitrix\Crm\Security\Role\Manage\Entity\FilterableByCategory as IFilterableByCategory;

/** @implements IFilterableByCategory */
trait FilterableByCategory
{
	protected ?int $filterByCategoryId = null;

	public function filterByCategory(?int $id = null): self
	{
		$this->filterByCategoryId = $id;

		return $this;
	}

	/**
	 * @param Category[] $categories
	 * @return Category[]
	 */
	protected function filterCategories(array $categories): array
	{
		if ($this->filterByCategoryId === null)
		{
			return $categories;
		}

		return array_filter($categories, fn (Category $category) => $category->getId() === $this->filterByCategoryId);
	}

	/**
	 * @param EO_ItemCategory[] $categories
	 * @return EO_ItemCategory[]
	 */
	protected function filterItemCategories(array $categories): array
	{
		if ($this->filterByCategoryId === null)
		{
			return $categories;
		}

		return array_filter($categories, fn (EO_ItemCategory $category) => $category->getId() === $this->filterByCategoryId);
	}
}
