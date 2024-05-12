<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\Controller\Entity;
use Bitrix\Crm\Integration\Main\UISelector\CrmDynamics;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Search;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\UI\EntitySelector;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Collection;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\RecentItem;
use Bitrix\UI\EntitySelector\SearchQuery;
use Bitrix\UI\EntitySelector\Tab;

abstract class EntityProvider extends BaseProvider
{
	protected $withRequisites = false;
	protected bool $showTab = false;

	public function __construct(array $options = [])
	{
		parent::__construct();

		Container::getInstance()->getLocalization()->loadMessages();

		$this->withRequisites = (bool)($options['withRequisites'] ?? $this->withRequisites);
		$this->options['withRequisites'] = $this->withRequisites;

		// tabs are currently supported in contacts and companies, for other providers you need to add icons first
		$this->showTab = (bool)($options['showTab'] ?? $this->showTab);
		$this->options['showTab'] = $this->showTab;
	}

	abstract protected function getEntityTypeId(): int;

	protected function getEntityTypeName(): string
	{
		return \CCrmOwnerType::ResolveName($this->getEntityTypeId());
	}

	public function isAvailable(): bool
	{
		$restriction = RestrictionManager::getSearchLimitRestriction();

		return
			EntityAuthorization::checkReadPermission($this->getEntityTypeId(), 0)
			&& !$restriction->isExceeded($this->getEntityTypeId())
		;
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
		$items = [];
		Collection::normalizeArrayValuesByInt($ids);
		if (empty($ids))
		{
			return $items;
		}

		$ids = $this->filterOutNonExistentEntryIds($ids);

		//todo remove queries in cycle!
		foreach ($ids as $entryId)
		{
			$items[] = $this->makeItem($entryId);
		}

		return $items;
	}

	protected function filterOutNonExistentEntryIds(array $ids): array
	{
		return $this->fetchEntryIds([
			'@ID' => $ids,
		]);
	}

	/**
	 * Returns entry ids by the filter
	 * @param array $filter - The same structure as in ORM
	 *
	 * @return int[]
	 */
	abstract protected function fetchEntryIds(array $filter): array;

	protected function makeItem(int $entityId): ?Item
	{
		$canReadItem = EntityAuthorization::checkReadPermission($this->getEntityTypeId(), $entityId);

		$entityInfo = $this->getEntityInfo($entityId, $canReadItem);
		$this->prepareItemAvatar($entityInfo);

		return new Item([
			'id' => $entityId,
			'entityId' => $this->getItemEntityId(),
			'title' => (string)$entityInfo['title'],
			'subtitle' => $entityInfo['desc'],
			'link' => $entityInfo['url'],
			'linkTitle' => Loc::getMessage('CRM_COMMON_DETAIL'),
			'avatar' => $entityInfo['image'],
			'searchable' => true,
			'hidden' => !$canReadItem,
			'tabs' => $this->getTabsNames(),
			'customData' => [
				'id' => (string)$entityId,
				'entityInfo' => $entityInfo,
			],
		]);
	}

	protected function getEntityInfo(int $entityId, bool $canReadItem): array
	{
		return \CCrmEntitySelectorHelper::PrepareEntityInfo(
			$this->getEntityTypeNameForMakeItemMethod(),
			$entityId,
			[
				'ENTITY_EDITOR_FORMAT' => true,
				'IS_HIDDEN' => !$canReadItem,
				'REQUIRE_REQUISITE_DATA' => true,
				'REQUIRE_MULTIFIELDS' => true,
				'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
				'REQUIRE_EDIT_REQUISITE_DATA' => $this->withRequisites,
				'LARGE_IMAGES' => true,
			]
		);
	}

	protected function prepareItemAvatar(array &$entityInfo): void
	{
		if (empty($entityInfo['image']))
		{
			$entityInfo['image'] = $this->getDefaultItemAvatar();
		}
	}

	protected function getDefaultItemAvatar(): ?string
	{
		return null;
	}

	protected function getEntityTypeNameForMakeItemMethod()
	{
		return $this->getEntityTypeName();
	}

	protected function getItemEntityId(): string
	{
		return mb_strtolower($this->getEntityTypeName());
	}

	private function getTabsNames(): array
	{
		return [$this->getEntityTypeName()];
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
			$moreItemIds = $this->getRecentItemIds($context);

			foreach ($moreItemIds as $itemId)
			{
				if ($remainingItemsCount === 0)
				{
					break;
				}

				$recentItem = new RecentItem([
					'id' => $itemId,
					'entityId' => $itemEntityId,
				]);

				if (!$recentItems->has($recentItem))
				{
					$recentItems->add($recentItem);
					$remainingItemsCount--;
				}
			}
		}

		$this->addTab($dialog);
	}

	private function addTab(Dialog $dialog): void
	{
		if (!$this->showTab)
		{
			return;
		}

		$icon = $this->getTabIcon();

		if (!$icon)
		{
			return;
		}

		$tab = new Tab([
			'id' => $this->getEntityTypeName(),
			'title' => \CCrmOwnerType::GetCategoryCaption($this->getEntityTypeId()),
			'stub' => true,
			'icon' => [
				'default' => $icon,
				'selected' => str_replace('ABB1B8', 'FFF', $icon),
			],
		]);

		$dialog->addTab($tab);
	}

	protected function getTabIcon(): ?string
	{
		return null;
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$searchProvider = Search\Result\Factory::createProvider($this->getEntityTypeId());
		$searchProvider->setLimit(CrmDynamics::LIMIT_SEARCH);
		$searchProvider->setAdditionalFilter($this->getAdditionalFilter());

		$result = $searchProvider->getSearchResult($searchQuery->getQuery());

		$wereAllResultsFoundForThisQuery = (count($result->getIds()) < $searchProvider->getLimit());
		$searchQuery->setCacheable($wereAllResultsFoundForThisQuery);

		$dialog->addItems($this->makeItemsByIds($result->getIds()));
	}

	protected function getAdditionalFilter(): array
	{
		return [];
	}

	protected function getCategoryId(): int
	{
		return 0;
	}

	protected function getRecentItemIds(string $context): array
	{
		$ids = [];

		$recentItems = Entity::getRecentlyUsedItems($context, $this->getItemEntityId(), [
			'EXPAND_ENTITY_TYPE_ID' => $this->getEntityTypeId(),
			'EXPAND_CATEGORY_ID' => $this->getCategoryId(),
		]);

		foreach ($recentItems as $item)
		{
			$ids[] = $item['ENTITY_ID'];
		}

		return $ids;
	}
}
