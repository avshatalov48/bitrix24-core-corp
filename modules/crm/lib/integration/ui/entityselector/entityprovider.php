<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Controller\Entity;
use Bitrix\Crm\Format\PersonNameFormatter;
use Bitrix\Crm\Integration\Main\UISelector\CrmDynamics;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Search;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\UI\EntitySelector;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Collection;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\RecentItem;
use Bitrix\UI\EntitySelector\SearchQuery;
use Bitrix\UI\EntitySelector\Tab;
use CCrmEntitySelectorHelper;
use CCrmOwnerType;

abstract class EntityProvider extends BaseProvider
{
	protected ?int $ownerId = null;

	protected bool $withRequisites = false;
	protected bool $showTab = false;
	protected bool $showEntityTypeNameInHeader = false;
	protected bool $hideClosedItems = false;

	protected UserPermissions $userPermissions;

	abstract protected function getEntityTypeId(): int;

	abstract protected function fetchEntryIds(array $filter): array;

	public function __construct(array $options = [])
	{
		parent::__construct();

		Container::getInstance()->getLocalization()->loadMessages();

		if (isset($options['ownerId']))
		{
			$this->ownerId = (int)$options['ownerId'];
		}

		$this->withRequisites = (bool)($options['withRequisites'] ?? $this->withRequisites);
		$this->options['withRequisites'] = $this->withRequisites;

		// tabs are currently supported in contacts and companies, for other providers you need to add icons first
		$this->showTab = (bool)($options['showTab'] ?? $this->showTab);
		$this->options['showTab'] = $this->showTab;

		if (isset($options['showEntityTypeNameInHeader']))
		{
			$this->showEntityTypeNameInHeader = (bool)$options['showEntityTypeNameInHeader'];
		}

		if (isset($options['hideClosedItems']))
		{
			$this->hideClosedItems = (bool)$options['hideClosedItems'];
		}

		$this->userPermissions = Container::getInstance()->getUserPermissions();
	}

	public function isAvailable(): bool
	{
		$restriction = RestrictionManager::getSearchLimitRestriction();

		return
			$this->userPermissions->checkReadPermissions($this->getEntityTypeId())
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

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$searchProvider = Search\Result\Factory::createProvider($this->getEntityTypeId());
		$searchProvider->setLimit(CrmDynamics::LIMIT_SEARCH);
		$searchProvider->setAdditionalFilter($this->getAdditionalFilter());

		$result = $searchProvider->getSearchResult($searchQuery->getQuery());

		$wasFulltextUsed = \Bitrix\Main\Search\Content::canUseFulltextSearch($searchQuery->getQuery());
		$wereAllResultsFoundForThisQuery = (count($result->getIds()) < $searchProvider->getLimit());

		$searchQuery->setCacheable($wasFulltextUsed && $wereAllResultsFoundForThisQuery);

		$dialog->addItems($this->makeItemsByIds($result->getIds()));
	}

	protected function getEntityTypeName(): string
	{
		return CCrmOwnerType::ResolveName($this->getEntityTypeId());
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

	protected function makeItem(int $entityId): ?Item
	{
		$isClosedItem = ComparerBase::isClosed(
			new ItemIdentifier($this->getEntityTypeId(), $entityId),
			false
		);
		$canReadItem = $this->userPermissions->checkReadPermissions($this->getEntityTypeId(), $entityId);
		$entityInfo = $this->getEntityInfo($entityId, $canReadItem);
		$this->prepareItemAvatar($entityInfo);

		$isHidden = !$canReadItem;
		if ($this->hideClosedItems)
		{
			$isHidden = $isHidden || $isClosedItem;
		}

		if (isset($this->ownerId))
		{
			$isHidden = $isHidden || $this->ownerId === $entityId;
		}

		$itemOptions = [
			'id' => $entityId,
			'entityId' => $this->getItemEntityId(),
			'title' => $this->getEntityTitle($entityInfo),
			'subtitle' => $entityInfo['desc'],
			'link' => $entityInfo['url'],
			'linkTitle' => Loc::getMessage('CRM_COMMON_DETAIL'),
			'avatar' => $entityInfo['image'],
			'searchable' => true,
			'hidden' => $isHidden,
			'tabs' => $this->getTabsNames(),
			'customData' => [
				'id' => (string)$entityId,
				'entityInfo' => $entityInfo,
			],
		];

		if ($this->showEntityTypeNameInHeader)
		{
			$itemOptions['supertitle'] = (string)$entityInfo['typeNameTitle'];
			$itemOptions['title'] = $this->getEntityTitle($entityInfo);
			$itemOptions['subtitle'] = null;
		}

		return new Item($itemOptions);
	}

	protected function getEntityInfo(int $entityId, bool $canReadItem): array
	{
		return CCrmEntitySelectorHelper::PrepareEntityInfo(
			$this->getEntityTypeNameForMakeItemMethod(),
			$entityId,
			[
				'ENTITY_EDITOR_FORMAT' => true,
				'IS_HIDDEN' => !$canReadItem,
				'REQUIRE_REQUISITE_DATA' => true,
				'REQUIRE_MULTIFIELDS' => true,
				'NAME_TEMPLATE' => PersonNameFormatter::getFormat(),
				'REQUIRE_EDIT_REQUISITE_DATA' => $this->withRequisites,
				'LARGE_IMAGES' => true,
			]
		);
	}

	protected function getEntityTypeNameForMakeItemMethod(): string
	{
		return $this->getEntityTypeName();
	}

	protected function getItemEntityId(): string
	{
		return mb_strtolower($this->getEntityTypeName());
	}

	protected function getTabIcon(): ?string
	{
		return null;
	}

	protected function getAdditionalFilter(): array
	{
		return [];
	}

	protected function getCategoryId(): int
	{
		return 0;
	}

	/**
	 * @param string $context
	 * @return array
	 *
	 * Generates a list of the last found items.
	 * If the filters narrow down the list of potentially available elements too much
	 * (for example, selecting contacts linked to a deal),
	 * we recommend redefining this method in the successor class and filtering at the selection stage.
	 */
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

	protected function getDefaultItemAvatar(): ?string
	{
		return null;
	}

	protected function prepareItemAvatar(array &$entityInfo): void
	{
		if (empty($entityInfo['image']))
		{
			$entityInfo['image'] = $this->getDefaultItemAvatar();
		}
	}

	protected function getTabsNames(): array
	{
		return [$this->getEntityTypeName()];
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
			'title' => CCrmOwnerType::GetCategoryCaption($this->getEntityTypeId()),
			'stub' => true,
			'icon' => [
				'default' => $icon,
				'selected' => str_replace('ABB1B8', 'FFF', $icon),
			],
		]);

		$dialog->addTab($tab);
	}

	protected function getEntityTitle(array $entityInfo): string
	{
		return (string)($entityInfo['title'] ?? '');
	}
}
