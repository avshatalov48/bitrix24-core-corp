<?php

namespace Bitrix\Crm\Service\Broker;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\EO_Status;
use Bitrix\Crm\EO_Status_Collection;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\StatusTable;

final class Stage
{
	/** @var array<string, EO_Status> */
	private array $stages = [];

	/** @var array<?int, EO_Status_Collection> */
	private array $stageCollections = [];

	private const CACHE_TTL = 86400;
	private string|StatusTable $tableClassName = StatusTable::class;

	public function __construct(
		private readonly Factory $factory,
	)
	{
	}

	public function getById(string $statusId): ?EO_Status
	{
		$stage = $this->getByIdFromCache($statusId);
		if ($stage !== null)
		{
			return $stage;
		}

		if (!$this->factory->isCategoriesSupported())
		{
			return $this
				->loadByCategoryId()
				->getByIdFromCache($statusId);
		}

		foreach ($this->factory->getCategories() as $category)
		{
			$stage = $this
				->loadByCategoryId($category->getId())
				->getByIdFromCache($statusId);

			if ($stage !== null)
			{
				return $stage;
			}
		}

		return null;
	}

	public function getByCategoryId(?int $categoryId = null): EO_Status_Collection
	{
		return $this
			->loadByCategoryId($categoryId)
			->getByCategoryIdFromCache($categoryId);
	}

	public function getByCategoryIds(array $categoryIds): EO_Status_Collection
	{
		return $this
			->loadByCategoryIds($categoryIds)
			->getByCategoryIdsFromCache($categoryIds);
	}

	private function loadByCategoryId(?int $categoryId = null): self
	{
		if ($this->getByCategoryIdFromCache($categoryId) !== null)
		{
			return $this;
		}

		$stages = $this->fetchByCategoryId($categoryId);
		$this->setCache($categoryId, $stages);

		return $this;
	}

	private function loadByCategoryIds(array $categoryIds): self
	{
		$loadedCategoryIds = array_keys($this->stageCollections);
		$missedCategoryIds = array_diff($categoryIds, $loadedCategoryIds);
		if (empty($missedCategoryIds))
		{
			return $this;
		}

		array_map([$this, 'loadByCategoryId'], $missedCategoryIds);

		return $this;
	}

	private function fetchByCategoryId(?int $categoryId = null): EO_Status_Collection
	{
		$entityId = $this->factory->getStagesEntityId($categoryId);

		return $this->tableClassName::query()
			->where('ENTITY_ID', $entityId)
			->addOrder('SORT')
			->setCacheTtl(self::CACHE_TTL)
			->fetchCollection();
	}

	private function getByCategoryIdFromCache(?int $categoryId = null): ?EO_Status_Collection
	{
		return $this->stageCollections[$categoryId] ?? null;
	}

	private function getByCategoryIdsFromCache(array $categoryIds): ?EO_Status_Collection
	{
		$stageCollection = new EO_Status_Collection();
		foreach ($categoryIds as $categoryId)
		{
			if (!isset($this->stageCollections[$categoryId]))
			{
				return null;
			}

			$stageCollection->merge($this->stageCollections[$categoryId]);
		}

		return $stageCollection;
	}

	private function getByIdFromCache(string $statusId): ?EO_Status
	{
		return $this->stages[$statusId] ?? null;
	}

	private function setCache(?int $categoryId, EO_Status_Collection $stages): void
	{
		$this->stageCollections[$categoryId] = $stages;

		foreach ($stages->getAll() as $stage)
		{
			$this->stages[$stage->getStatusId()] = $stage;
		}
	}

	public function clearCache(): void
	{
		$this->stages = [];
		$this->stageCollections = [];
	}
}
