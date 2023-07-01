<?php

namespace Bitrix\Tasks\Integration\UI\EntitySelector;

use Bitrix\Main\Loader;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Scrum\Form\EpicForm;
use Bitrix\Tasks\Scrum\Service\EpicService;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\PreselectedItem;
use Bitrix\UI\EntitySelector\RecentItem;
use Bitrix\UI\EntitySelector\SearchQuery;

class EpicSelectorProvider extends BaseProvider
{
	private $entityId = 'epic-selector';
	private $maxCount = 30;

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->options['groupId'] = (int) ($options['groupId'] ?? null);
	}

	public function isAvailable(): bool
	{
		if (!$GLOBALS['USER']->isAuthorized())
		{
			return false;
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		$groupId = $this->getOption('groupId');

		$group = Workgroup::getById($groupId);
		if (!$group || !$group->isScrumProject())
		{
			return false;
		}

		return Group::canReadGroupTasks($GLOBALS['USER']->getId(), $groupId);
	}

	public function getItems(array $ids): array
	{
		$groupId = $this->getOption('groupId');

		return $this->getSelectedEpicItems($groupId, $ids);
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$groupId = $this->getOption('groupId');

		$dialog->addItems($this->getEpicItems($groupId, [], $searchQuery));

		if ($dialog->getItemCollection()->count() >= $this->maxCount)
		{
			$searchQuery->setCacheable(false);
		}
	}

	public function fillDialog(Dialog $dialog): void
	{
		$groupId = $this->getOption('groupId');

		$this->fillWithPreselectedItems($groupId, $dialog);
		$this->fillWithRecentItems($groupId, $dialog);

		if ($dialog->getItemCollection()->count() < $this->maxCount)
		{
			$excludeIds = $this->getRecentItemsIds($dialog);

			$dialog->addItems($this->getEpicItems($groupId, $excludeIds));
		}
	}

	/**
	 * @param int $groupId Group id.
	 * @return Item[]
	 */
	private function getEpicItems(int $groupId, array $excludeIds = [], ?SearchQuery $searchQuery = null): array
	{
		$items = [];

		foreach ($this->getEpics($groupId, $excludeIds, $searchQuery) as $epic)
		{
			$items[] = $this->getItem($epic);
		}

		return $items;
	}

	/**
	 * @param int $groupId Group id.
	 * @return EpicForm[]
	 */
	private function getEpics(int $groupId, array $excludeIds = [], ?SearchQuery $searchQuery = null): array
	{
		$epics = [];

		$epicService = new EpicService($GLOBALS['USER']->getId());

		$nav = $this->getNavigation($this->maxCount);

		$select = [];

		$filter = ['=GROUP_ID' => $groupId];
		if ($excludeIds)
		{
			$filter['!ID'] = $excludeIds;
		}
		if ($searchQuery)
		{
			$filter['?NAME'] = $searchQuery->getQuery();
		}

		$queryResult = $epicService->getList(
			$select,
			$filter,
			['ID' => 'DESC'],
			$nav
		);
		$n = 0;
		while ($data = $queryResult->fetch())
		{
			if ($nav && (++$n > $nav->getPageSize()))
			{
				break;
			}

			$epic = new EpicForm();
			$epic->fillFromDatabase($data);

			$epics[] = $epic;
		}

		return $epics;
	}

	/**
	 * @param int $groupId Group id.
	 * @return Item[]
	 */
	private function getSelectedEpicItems(int $groupId, array $ids): array
	{
		if (empty($ids))
		{
			return [];
		}

		$items = [];

		$epicService = new EpicService($GLOBALS['USER']->getId());

		$queryResult = $epicService->getList(
			[],
			[
				'=ID' => $ids,
				'=GROUP_ID' => $groupId,
			],
			['ID' => 'DESC'],
		);

		while ($data = $queryResult->fetch())
		{
			$epic = new EpicForm();

			$epic->fillFromDatabase($data);

			$items[$epic->getId()] = $this->getItem($epic);
		}

		return $items;
	}

	private function getItem(EpicForm $epic): Item
	{
		return new Item([
			'entityId' => $this->entityId,
			'id' => $epic->getId(),
			'title' => $epic->getName(),
			'tabs' => 'recents',
			'avatarOptions' => [
				'bgColor' => $epic->getColor(),
				'bgImage' => 'none',
				'borderRadius' => '12px',
			],
		]);
	}

	private function getNavigation(int $maxCount): PageNavigation
	{
		$navigation = new PageNavigation('epic-selector-provider');

		$navigation->setCurrentPage(1);
		$navigation->setPageSize($maxCount);

		return $navigation;
	}

	private function fillWithPreselectedItems(int $groupId, Dialog $dialog): void
	{
		if ($dialog->getPreselectedCollection()->count() < 1)
		{
			return;
		}

		$epics = $this->getSelectedEpicItems(
			$groupId,
			$this->getPreselectedItemsIds($dialog)
		);

		$sort = 0;
		foreach ($epics as $epicItem)
		{
			$epicItem->setSort(++$sort);
			$dialog->addItem($epicItem);
		}
	}

	private function fillWithRecentItems(int $groupId, Dialog $dialog): void
	{
		if ($dialog->getRecentItems()->count() < 1)
		{
			return;
		}

		$epics = $this->getSelectedEpicItems(
			$groupId,
			array_diff(
				$this->getRecentItemsIds($dialog),
				$this->getPreselectedItemsIds($dialog)
			)
		);

		foreach ($dialog->getRecentItems()->getAll() as $item)
		{
			/** @var RecentItem $item */
			$epicId = $item->getId();

			if (
				!array_key_exists($epicId, $epics)
				|| $dialog->getItemCollection()->get($this->entityId, $epicId)
			)
			{
				continue;
			}

			$dialog->addItem($epics[$epicId]);

			if ($dialog->getItemCollection()->count() >= $this->maxCount)
			{
				break;
			}
		}
	}

	private function getPreselectedItemsIds(Dialog $dialog): array
	{
		$items = $dialog->getPreselectedCollection()->getAll();

		return array_map(
			static function (PreselectedItem $item) {
				return $item->getId();
			},
			$items
		);
	}

	private function getRecentItemsIds(Dialog $dialog): array
	{
		$items = $dialog->getRecentItems()->getAll();

		return array_map(
			static function (RecentItem $item) {
				return $item->getId();
			},
			$items
		);
	}
}