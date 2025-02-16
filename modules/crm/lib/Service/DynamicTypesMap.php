<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\EO_Status;
use Bitrix\Crm\Item;
use Bitrix\Crm\Model\Dynamic\EO_Type_Collection;
use Bitrix\Crm\Model\Dynamic\Type;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Model\ItemCategoryTable;
use Bitrix\Crm\StatusTable;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\ORM\Objectify\Collection;

class DynamicTypesMap
{
	protected const DYNAMIC_COLLECTION_CACHE_TTL = 86400;
	protected const DYNAMIC_COLLECTION_CACHE_ID = 'crm_dynamic_types_collection';
	protected const DYNAMIC_COLLECTION_CACHE_PATH = 'crm';

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
	protected $isStagesEnabled = [];
	protected $isCategoriesEnabled = [];

	private ?EO_Type_Collection $typesCollection = null;

	public function __construct()
	{
		$this->typeDataClass = Container::getInstance()->getDynamicTypeDataClass();
	}

	/**
	 * @param array $params = [
	 *     'isLoadStages' => true,
	 *     'isLoadCategories' => true,
	 * ]
	 *
	 * @return $this
	 */
	final public function load(array $params = []): self
	{
		$isLoadStages = $params['isLoadStages'] ?? true;
		$isLoadCategories = $params['isLoadCategories'] ?? true;
		if ($isLoadStages)
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

		if (!$this->isTypesLoaded)
		{
			$this->isTypesLoaded = true;
			foreach ($this->getTypesCollection() as $type)
			{
				$entityTypeId = $type->getEntityTypeId();

				if (!\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
				{
					continue;
				}

				$factory = Container::getInstance()->getDynamicFactoryByType($type);
				$stagesFieldName = null;
				if ($factory->isStagesSupported())
				{
					$stagesFieldName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_STAGE_ID);
				}
				$this->types[$entityTypeId] = $type;
				$this->stageFieldNames[$entityTypeId] = $stagesFieldName;
				$this->isAutomationEnabled[$entityTypeId] = $factory->isAutomationEnabled();
				$this->isStagesEnabled[$entityTypeId] = $factory->isStagesEnabled();
				$this->isCategoriesEnabled[$entityTypeId] = $factory->isCategoriesEnabled();
			}
		}
		$entityTypeIds = array_keys($this->types);

		if (empty($entityTypeIds))
		{
			$this->isCategoriesLoaded = true;
			$this->isStagesLoaded = true;
			return $this;
		}

		if (!$isLoadCategories)
		{
			return $this;
		}

		if(!$this->isCategoriesLoaded)
		{
			foreach (static::$categoryDataClass::getList([
					'filter' => [
						'@ENTITY_TYPE_ID' => $entityTypeIds,
					],
					'cache' => [
						'ttl' => self::DYNAMIC_COLLECTION_CACHE_TTL,
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

		if (!$this->isStagesLoaded)
		{
			foreach (static::$stagesDataClass::getList([
					'order' => [
						'SORT' => 'ASC',
					],
					'filter' => [
						'@ENTITY_ID' => array_keys($this->stageEntityIds),
					],
					'cache' => [
						'ttl' => self::DYNAMIC_COLLECTION_CACHE_TTL,
					],
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

	final public function getBunchOfTypesByIds(array $typeIds): array
	{
		return array_filter(
			$this->getTypes(),
			fn(Type $type) => in_array($type->getId(), $typeIds, true),
		);
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

	public function isStagesEnabled(int $entityTypeId): bool
	{
		return $this->isStagesEnabled[$entityTypeId] ?? false;
	}

	public function isCategoriesEnabled(int $entityTypeId): bool
	{
		return $this->isCategoriesEnabled[$entityTypeId] ?? false;
	}

	/**
	 * Load types collection from cache of possible.
	 * If cache is not valid - loads it from database and stores new value.
	 *
	 * @return Collection
	 */
	public function getTypesCollection(): Collection
	{
		if ($this->typesCollection)
		{
			return $this->typesCollection;
		}

		$typesData = null;

		$cache = Application::getInstance()->getCache();
		if ($cache->initCache(
			static::DYNAMIC_COLLECTION_CACHE_TTL,
			static::DYNAMIC_COLLECTION_CACHE_ID,
			static::DYNAMIC_COLLECTION_CACHE_PATH
		))
		{
			$cacheVars = $cache->getVars();
			if (isset($cacheVars['typesData']) && is_array($cacheVars['typesData']))
			{
				$typesData = $cacheVars['typesData'];
			}
		}

		if ($typesData === null)
		{
			if (Application::getConnection()->isTableExists($this->typeDataClass::getTableName()))
			{
				try
				{
					$typesData = $this->typeDataClass::getList([
						'filter' => [
							'=IS_INITIALIZED' => true,
						],
					])->fetchAll();
				}
				catch (SqlQueryException $e)
				{
					$typesData = null;
				}
			}
			else
			{
				$typesData = [];
			}

			if ($typesData !== null)
			{
				$cache->startDataCache();
				$cache->endDataCache([
					'typesData' => $typesData,
				]);
			}
			else
			{
				$typesData = [];
			}
		}

		$this->typesCollection = $this->typeDataClass::wakeUpCollection($typesData);

		return $this->typesCollection;
	}

	public function invalidateTypesCollectionCache(): void
	{
		$cache = Application::getInstance()->getCache();
		$cache->clean(static::DYNAMIC_COLLECTION_CACHE_ID, static::DYNAMIC_COLLECTION_CACHE_PATH);
		$this->isTypesLoaded = false;
		$this->typesCollection = null;
	}
}
