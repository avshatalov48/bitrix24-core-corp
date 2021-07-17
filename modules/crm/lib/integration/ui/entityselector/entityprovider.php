<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\Controller\Entity;
use Bitrix\Crm\Search\SearchEnvironment;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Collection;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\RecentItem;
use Bitrix\UI\EntitySelector\SearchQuery;

abstract class EntityProvider extends BaseProvider
{
	protected $withRequisites = false;

	public function __construct(array $options = [])
	{
		parent::__construct();

		Container::getInstance()->getLocalization()->loadMessages();

		$this->withRequisites = (bool)($options['withRequisites'] ?? $this->withRequisites);
		$this->options['withRequisites'] = $this->withRequisites;
	}

	abstract protected function getEntityTypeId(): int;

	protected function getEntityTypeName(): string
	{
		return \CCrmOwnerType::ResolveName($this->getEntityTypeId());
	}

	public function isAvailable(): bool
	{
		return \CCrmAuthorizationHelper::CheckReadPermission($this->getEntityTypeId(), 0);
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
		$ids = $this->fetchEntryIds([
			'@ID' => $ids,
		]);

		//todo remove queries in cycle!
		foreach ($ids as $entryId)
		{
			$items[] = $this->makeItem($entryId);
		}

		return $items;
	}

	protected function makeItem(int $entityId): ?Item
	{
		$canReadItem = EntityAuthorization::checkReadPermission($this->getEntityTypeId(), $entityId);

		$entityInfo = \CCrmEntitySelectorHelper::PrepareEntityInfo(
			$this->getEntityTypeNameForMakeItemMethod(),
			$entityId,
			[
				'ENTITY_EDITOR_FORMAT' => true,
				'IS_HIDDEN' => !$canReadItem,
				'REQUIRE_REQUISITE_DATA' => true,
				'REQUIRE_MULTIFIELDS' => true,
				'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
				'REQUIRE_EDIT_REQUISITE_DATA' => $this->withRequisites,
			]
		);

		return new Item([
			'id' => $entityId,
			'entityId' => $this->getItemEntityId(),
			'title' => $entityInfo['title'],
			'subtitle' => $entityInfo['desc'],
			'link' => $entityInfo['url'],
			'linkTitle' => Loc::getMessage('CRM_COMMON_DETAIL'),
			'avatar' => $entityInfo['image'],
			'searchable' => true,
			'hidden' => !$canReadItem,
			'customData' => [
				'entityInfo' => $entityInfo
			],
		]);
	}

	protected function getEntityTypeNameForMakeItemMethod()
	{
		return $this->getEntityTypeName();
	}

	protected function getItemEntityId(): string
	{
		return mb_strtolower($this->getEntityTypeName());
	}

	public function fillDialog(Dialog $dialog): void
	{
		$context = $dialog->getContext();
		if(!empty($context))
		{
			$recentItems = $dialog->getRecentItems()->getEntityItems($this->getItemEntityId());
			if(count($recentItems) < Entity::ITEMS_LIMIT)
			{
				$moreItemIds = $this->getRecentItemIds($context);
				foreach($moreItemIds as $itemId)
				{
					$dialog->getRecentItems()->add(new RecentItem([
						'id' => $itemId,
						'entityId' => $this->getItemEntityId(),
					]));
				}
			}
		}
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$query = $searchQuery->getQuery();
		if (empty($query))
		{
			return;
		}

		$filter = [
			'SEARCH_CONTENT' => $query
		];
		SearchEnvironment::prepareSearchFilter($this->getEntityTypeId(), $filter);

		$ids = $this->fetchEntryIds($filter);
		$dialog->addItems($this->makeItemsByIds($ids));
	}

	/**
	 * Returns entry ids by the filter
	 * @param array $filter - The same structure as in ORM
	 *
	 * @return int[]
	 */
	abstract protected function fetchEntryIds(array $filter): array;

	protected function getRecentItemIds(string $context): array
	{
		$ids = [];

		$recentItems = Entity::getRecentlyUsedItems($context, $this->getItemEntityId(), [
			'EXPAND_ENTITY_TYPE_ID' => $this->getEntityTypeId(),
		]);

		foreach($recentItems as $item)
		{
			$ids[] = $item['ENTITY_ID'];
		}

		return $ids;
	}
}