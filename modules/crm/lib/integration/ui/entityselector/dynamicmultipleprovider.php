<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\Controller\Entity;
use Bitrix\Crm\Format\PersonNameFormatter;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Search;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\UI\EntitySelector;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\RecentItem;
use Bitrix\UI\EntitySelector\SearchQuery;

class DynamicMultipleProvider extends BaseProvider
{
	public const DYNAMIC_MULTIPLE_ID = 'dynamic_multiple';

	public function __construct(array $options = [])
	{
		parent::__construct();

		Container::getInstance()->getLocalization()->loadMessages();

		if (!isset($options['dynamicTypeIds']) || !is_array($options['dynamicTypeIds']))
		{
			$options['dynamicTypeIds'] = $this->loadDynamicTypeIds();
		}

		$this->options['dynamicTypeIds'] = $this->prepareDynamicTypeIds($options['dynamicTypeIds']);
	}

	private function loadDynamicTypeIds(): array
	{
		$ids = [];

		$typesMap = Container::getInstance()->getDynamicTypesMap()->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		]);
		foreach ($typesMap->getTypes() as $type)
		{
			$ids[] = $type->getEntityTypeId();
		}

		return $ids;
	}

	private function prepareDynamicTypeIds(array $dynamicTypeIds): array
	{
		$preparedIds = [];

		if (!empty($dynamicTypeIds))
		{
			$restriction = RestrictionManager::getSearchLimitRestriction();

			foreach ($dynamicTypeIds as $entityTypeId)
			{
				if (
					\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
					&& EntityAuthorization::checkReadPermission($entityTypeId, 0)
					&& !$restriction->isExceeded($entityTypeId)
				)
				{
					$preparedIds[] = $entityTypeId;
				}
			}
		}

		return $preparedIds;
	}

	public static function prepareId(int $entityTypeId, int $entityId): string
	{
		return "{$entityTypeId}:{$entityId}";
	}

	public static function parseId(string $id): array
	{
		$parts = explode(':', $id);
		if (count($parts) !== 2)
		{
			return [null, null];
		}

		$entityTypeId = (int)($parts[0] ?? 0) ?: null;
		$entityId = (int)($parts[1] ?? 0) ?: null;

		return [$entityTypeId, $entityId];
	}

	private function getDynamicEntityIds(): array
	{
		return $this->getOption('dynamicTypeIds');
	}

	public function isAvailable(): bool
	{
		return !empty($this->getDynamicEntityIds());
	}

	public function getItems(array $ids): array
	{
		return $this->makeItemsByIds($ids);
	}

	public function getSelectedItems(array $ids): array
	{
		return $this->makeItemsByIds($ids);
	}

	protected function makeItemsByIds(array $ids): array
	{
		$idsByEntityId = $this->splitIdsByEntityId($ids);

		if (empty($idsByEntityId))
		{
			return [];
		}

		$items = [];

		foreach ($idsByEntityId as $entityTypeId => $idsList)
		{
			$items[] = $this->makeItemsByTypeAndId($entityTypeId, $idsList);
		}

		if (!empty($items))
		{
			$items = array_merge(...$items);
		}

		return $items;
	}

	protected function splitIdsByEntityId(array $ids): array
	{
		$idsByEntityId = [];

		foreach ($ids as $id)
		{
			[$entityTypeId, $entityId] = static::parseId($id);

			if ($entityTypeId !== null && $entityId !== null)
			{
				$idsByEntityId[$entityTypeId][] = $entityId;
			}
		}

		$allowedEntityIds = $this->getDynamicEntityIds();

		foreach ($idsByEntityId as $entityId => $list)
		{
			if (in_array($entityId, $allowedEntityIds, true))
			{
				$idsByEntityId[$entityId] = array_values(array_unique($list));
			}
			else
			{
				unset($idsByEntityId[$entityId]);
			}
		}

		return $idsByEntityId;
	}

	protected function makeItemsByTypeAndId(int $entityTypeId, array $ids): array
	{
		$items = [];

		$idsList = $this->filterOutNonExistentEntityIds($entityTypeId, $ids);
		foreach ($idsList as $entityId)
		{
			$items[] = $this->makeItem($entityTypeId, $entityId);
		}

		return $items;
	}

	protected function filterOutNonExistentEntityIds(int $entityTypeId, array $ids): array
	{
		if (empty($ids))
		{
			return [];
		}

		return $this->fetchEntryIds($entityTypeId, [
			'@ID' => $ids,
		]);
	}

	/**
	 * Returns entry ids by the filter
	 *
	 * @param int $entityTypeId
	 * @param array $filter - The same structure as in ORM
	 *
	 * @return int[]
	 */
	protected function fetchEntryIds(int $entityTypeId, array $filter): array
	{
		$result = [];

		$factory = Container::getInstance()->getFactory($entityTypeId);
		if ($factory)
		{
			$items = $factory->getItemsFilteredByPermissions([
				'select' => ['ID'],
				'filter' => $filter,
			]);

			foreach ($items as $item)
			{
				$result[] = $item->getId();
			}
		}

		return $result;
	}

	protected function makeItem(int $entityTypeId, int $entityId): ?Item
	{
		$canReadItem = EntityAuthorization::checkReadPermission($entityTypeId, $entityId);

		$entityInfo = \CCrmEntitySelectorHelper::PrepareEntityInfo(
			$this->getEntityTypeNameForMakeItemMethod($entityTypeId),
			$entityId,
			[
				'ENTITY_EDITOR_FORMAT' => true,
				'IS_HIDDEN' => !$canReadItem,
				'REQUIRE_REQUISITE_DATA' => true,
				'REQUIRE_MULTIFIELDS' => true,
				'NAME_TEMPLATE' => PersonNameFormatter::getFormat(),
				'REQUIRE_EDIT_REQUISITE_DATA' => false,
				'LARGE_IMAGES' => true,
			]
		);

		$entityInfo['id'] = "{$entityTypeId}:{$entityId}";
		$entityInfo['type'] = $this->getItemEntityId();

		return new Item([
			'id' => "{$entityTypeId}:{$entityId}",
			'entityId' => $this->getItemEntityId(),
			'title' => (string)$entityInfo['title'],
			'subtitle' => $entityInfo['desc'],
			'link' => $entityInfo['url'],
			'linkTitle' => Loc::getMessage('CRM_COMMON_DETAIL'),
			'avatar' => $entityInfo['image'],
			'searchable' => true,
			'hidden' => !$canReadItem,
			'customData' => [
				'entityInfo' => $entityInfo,
			],
		]);
	}

	protected function getEntityTypeNameForMakeItemMethod(int $entityTypeId)
	{
		return mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId));
	}

	protected function getItemEntityId(): string
	{
		return self::DYNAMIC_MULTIPLE_ID;
	}

	public function fillDialog(Dialog $dialog): void
	{
		$itemEntityId = $this->getItemEntityId();
		$recentItems = $dialog->getRecentItems();
		$recentItemsByEntityId = $recentItems->getEntityItems($itemEntityId);
		$remainingItemsCount = Entity::ITEMS_LIMIT - count($recentItemsByEntityId);

		if ($remainingItemsCount > 0)
		{
			foreach ($dialog->getGlobalRecentItems()->getEntityItems($this->getItemEntityId()) as $globalRecentItem)
			{
				if ($remainingItemsCount === 0)
				{
					break;
				}

				if (!$recentItems->has($globalRecentItem))
				{
					$recentItems->add($globalRecentItem);
					$remainingItemsCount--;
				}
			}
		}

		if ($remainingItemsCount > 0)
		{
			$context = $dialog->getContext() ?: EntitySelector::CONTEXT;

			foreach ($this->getDynamicEntityIds() as $entityTypeId)
			{
				$moreItemIds = $this->getRecentItemIds($entityTypeId, $context);

				foreach ($moreItemIds as $itemId)
				{
					$recentItem = new RecentItem([
						'id' => "{$entityTypeId}:{$itemId}",
						'entityId' => $itemEntityId,
					]);

					if (!$recentItems->has($recentItem))
					{
						$recentItems->add($recentItem);
						$remainingItemsCount--;
					}

					if ($remainingItemsCount === 0)
					{
						break;
					}
				}
			}
		}
	}

	protected function getRecentItemIds(int $entityTypeId, string $context): array
	{
		$ids = [];

		$recentItems = Entity::getRecentlyUsedItems($context, $this->getItemEntityId(), [
			'EXPAND_ENTITY_TYPE_ID' => $entityTypeId,
		]);

		foreach ($recentItems as $item)
		{
			$ids[] = $item['ENTITY_ID'];
		}

		return $ids;
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$countFound = 0;
		$maxLimit = 0;

		foreach ($this->getDynamicEntityIds() as $entityTypeId)
		{
			$searchProvider = Search\Result\Factory::createProvider($entityTypeId);
			$searchProvider->setAdditionalFilter($this->getAdditionalSearchFilter());

			$resultIds = $searchProvider->getSearchResult($searchQuery->getQuery())->getIds();

			$dialog->addItems($this->makeItemsByTypeAndId($entityTypeId, $resultIds));

			$countFound += count($resultIds);
			$maxLimit = max($maxLimit, $searchProvider->getLimit());

			if ($countFound > $searchProvider->getLimit())
			{
				break;
			}
		}

		$searchQuery->setCacheable($countFound < $maxLimit);
	}

	protected function getAdditionalSearchFilter(): array
	{
		return [];
	}
}
