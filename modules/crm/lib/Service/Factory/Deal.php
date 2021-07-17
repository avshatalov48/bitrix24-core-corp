<?php

namespace Bitrix\Crm\Service\Factory;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Category\Entity\DealCategory;
use Bitrix\Crm\Category\Entity\DealCategoryTable;
use Bitrix\Crm\Category\Entity\DealDefaultCategory;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\InvalidOperationException;

final class Deal extends Factory
{
	public function isAutomationEnabled(): bool
	{
		return true;
	}

	public function isBizProcEnabled(): bool
	{
		return true;
	}

	public function isCategoriesSupported(): bool
	{
		return true;
	}

	public function isCategoriesEnabled(): bool
	{
		return true;
	}

	public function getDataClass(): string
	{
		return DealTable::class;
	}

	public function isNewRoutingForDetailEnabled(): bool
	{
		return false;
	}

	public function isNewRoutingForListEnabled(): bool
	{
		return false;
	}

	public function isNewRoutingForAutomationEnabled(): bool
	{
		return false;
	}

	public function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Deal;
	}

	protected function getFieldsSettings(): array
	{
		return \CCrmDeal::GetFieldsInfo();
	}

	/**
	 * @inheritDoc
	 */
	public function getStagesEntityId(?int $categoryId = null): ?string
	{
		$categoryId = (int)$categoryId;

		if ($categoryId > 0)
		{
			return 'DEAL_STAGE_' . $categoryId;
		}

		return 'DEAL_STAGE';
	}

	public function createCategory(array $data = []): Category
	{
		$object = DealCategoryTable::createObject($data);

		return new DealCategory($object);
	}

	protected function loadCategories(): array
	{
		$defaultCategory = new DealDefaultCategory(
			\Bitrix\Crm\Category\DealCategory::getDefaultCategoryName(),
			\Bitrix\Crm\Category\DealCategory::getDefaultCategorySort()
		);

		$result = [$defaultCategory];

		$categories = DealCategoryTable::getList([
			'filter' => [
				'=IS_LOCKED' => 'N',
			],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC',
			]
		])->fetchCollection();
		foreach ($categories as $category)
		{
			$result[] = new DealCategory($category);
		}

		usort(
			$result,
			static function(Category $a, Category $b) {
				if ($a->getSort() === $b->getSort())
				{
					return 0;
				}

				return ($a->getSort() < $b->getSort()) ? -1 : 1;
			}
		);

		return $result;
	}

	protected function getTrackedFieldNames(): array
	{
		return [];
	}

	protected function getDependantTrackedObjects(): array
	{
		return [];
	}

	public function getItems(array $parameters = []): array
	{
		throw new InvalidOperationException('Deal factory is not ready to work with items yet');
	}

	public function getItem(int $id): ?Item
	{
		throw new InvalidOperationException('Deal factory is not ready to work with items yet');
	}

	public function createItem(array $data = []): Item
	{
		throw new InvalidOperationException('Deal factory is not ready to work with items yet');
	}

	public function getItemCategoryId(int $id): ?int
	{
		return \CCrmDeal::GetCategoryID($id);
	}
}
