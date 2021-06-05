<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Category\Entity\ItemCategory;
use Bitrix\Crm\EO_Status;
use Bitrix\Crm\Item;
use Bitrix\Crm\Model\Dynamic\Type;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Model\ItemCategoryTable;
use Bitrix\Crm\StatusTable;
use Bitrix\Main\InvalidOperationException;

class DynamicTypesMap
{
	/**
	 * @var string|TypeTable
	 */
	protected $typeDataClass = TypeTable::class;
	/**
	 * @var string|StatusTable
	 */
	protected static $stagesDataClass = StatusTable::class;
	/**
	 * @var string|ItemCategoryTable
	 */
	protected static $categoryDataClass = ItemCategoryTable::class;

	protected $isTypesLoaded = false;
	protected $isCategoriesLoaded = false;
	protected $isStagesLoaded = false;

	protected $types = [];
	protected $categories = [];
	protected $categoryIds = [];
	protected $defaultCategoryIds = [];
	protected $stages = [];
	protected $stageEntityIds = [];
	protected $stageFieldNames = [];
	protected $isAutomationEnabled = [];

	public function __construct()
	{
		$this->typeDataClass = Container::getInstance()->getDynamicTypeDataClass();
	}

	final public function load(array $params = []): self
	{
		$isLoadStages = $params['isLoadStages'] ?? true;
		$isLoadCategories = $params['isLoadCategories'] ?? true;
		if($isLoadStages)
		{
			// we have to load categories to properly load stages
			$isLoadCategories = true;
		}

		if ($this->isTypesLoaded
			&& (
				($isLoadCategories && $this->isCategoriesLoaded)
				|| !$isLoadCategories
			)
			&&
			(
				($isLoadStages && $this->isStagesLoaded)
				|| !$isLoadStages
			)
		)
		{
			// loaded everything that needed
			return $this;
		}

		if(!$this->isTypesLoaded)
		{
			$this->isTypesLoaded = true;
			foreach ($this->typeDataClass::getList()->fetchCollection() as $type)
			{
				$factory = Container::getInstance()
					->getDynamicFactoryByType($type);
				$stagesFieldName = null;
				if ($factory->isStagesSupported())
				{
					$stagesFieldName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_STAGE_ID);
				}
				$this->types[$type->getEntityTypeId()] = $type;
				$this->stageFieldNames[$type->getEntityTypeId()] = $stagesFieldName;
				$this->isAutomationEnabled[$type->getEntityTypeId()] = $factory->isAutomationEnabled();
			}
		}
		$entityTypeIds = array_keys($this->types);

		if (empty($entityTypeIds) || !$isLoadCategories)
		{
			return $this;
		}

		if(!$this->isCategoriesLoaded)
		{
			foreach (static::$categoryDataClass::getList([
					'filter' => [
						'@ENTITY_TYPE_ID' => $entityTypeIds,
					],
				])->fetchCollection() as $category)
			{
				$entityTypeId = $category->getEntityTypeId();
				$this->categoryIds[$category->getId()] = $entityTypeId;
				$this->categories[$entityTypeId][$category->getId()] = $category;
				if($category->getIsDefault())
				{
					$this->defaultCategoryIds[$entityTypeId] = $category->getId();
				}
				$stagesEntityId = Container::getInstance()
					->getDynamicFactoryByType($this->types[$entityTypeId])
					->getStagesEntityId($category->getId());
				if($stagesEntityId)
				{
					$this->stageEntityIds[$stagesEntityId] = $this->combineEntityAndCategory($entityTypeId, $category->getId());
				}
			}
			$this->isCategoriesLoaded = true;
		}

		if (empty($this->stageEntityIds) || !$isLoadStages)
		{
			return $this;
		}

		if(!$this->isStagesLoaded)
		{
			foreach (static::$stagesDataClass::getList([
					'filter' => [
						'@ENTITY_ID' => array_keys($this->stageEntityIds),
					]
				])->fetchCollection() as $stage)
			{
				$this->stages[$stage->getEntityId()][$stage->getStatusId()] = $stage;
			}
			$this->isStagesLoaded = true;
		}

		return $this;
	}

	protected function combineEntityAndCategory(int $entityTypeId, int $categoryId): string
	{
		return $entityTypeId . '_' . $categoryId;
	}

	/**
	 * @return Type[]
	 * @throws InvalidOperationException
	 */
	public function getTypes(): array
	{
		if(!$this->isTypesLoaded)
		{
			throw new InvalidOperationException('Map should be loaded first');
		}

		return $this->types;
	}

	/**
	 * @param int|null $entityTypeId
	 * @return \Bitrix\Crm\Model\EO_ItemCategory[]
	 * @throws InvalidOperationException
	 */
	public function getCategories(?int $entityTypeId = null): array
	{
		if(!$this->isCategoriesLoaded)
		{
			throw new InvalidOperationException('Categories should be loaded first');
		}

		return $entityTypeId ? $this->categories[$entityTypeId] ?? [] : $this->categories;
	}

	public function getDefaultCategory(int $entityTypeId): ?\Bitrix\Crm\Model\EO_ItemCategory
	{
		$categories = $this->getCategories($entityTypeId);
		foreach ($categories as $category)
		{
			if ($category->getIsDefault())
			{
				return $category;
			}
		}

		return null;
	}

	public function getStagesEntityId(int $entityTypeId, ?int $categoryId = null): ?string
	{
		if(!$this->isTypesLoaded)
		{
			throw new InvalidOperationException('Map should be loaded first');
		}

		if(!$categoryId)
		{
			$categoryId = $this->defaultCategoryIds[$entityTypeId];
		}

		return array_search(
			$this->combineEntityAndCategory($entityTypeId, $categoryId),
			$this->stageEntityIds,
			true
		);
	}

	public function getAllStages(): array
	{
		if(!$this->isStagesLoaded)
		{
			throw new InvalidOperationException('Stages should be loaded first');
		}

		return $this->stages;
	}

	/**
	 * @param int $entityTypeId
	 * @param int|null $categoryId
	 * @return EO_Status[]
	 * @throws InvalidOperationException
	 */
	public function getStages(int $entityTypeId, ?int $categoryId = null): array
	{
		if(!$this->isStagesLoaded)
		{
			throw new InvalidOperationException('Stages should be loaded first');
		}

		if(!$categoryId)
		{
			$categoryId = $this->defaultCategoryIds[$entityTypeId];
		}

		$stageEntityId = $this->getStagesEntityId($entityTypeId, $categoryId);

		return $this->stages[$stageEntityId] ?? [];
	}

	public function getStagesFieldName(int $entityTypeId): ?string
	{
		return $this->stageFieldNames[$entityTypeId] ?? null;
	}

	public function isAutomationEnabled(int $entityTypeId): bool
	{
		return $this->isAutomationEnabled[$entityTypeId] ?? false;
	}
}
