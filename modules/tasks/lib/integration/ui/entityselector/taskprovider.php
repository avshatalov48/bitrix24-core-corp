<?php

namespace Bitrix\Tasks\Integration\UI\EntitySelector;

use Bitrix\Main\Text\Emoji;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\RecentItem;
use Bitrix\UI\EntitySelector\SearchQuery;

class TaskProvider extends BaseProvider
{
	private static $entityId = 'task';
	private static $maxCount = 30;

	public function __construct(array $options = [])
	{
		parent::__construct();
	}

	public function isAvailable(): bool
	{
		return $GLOBALS['USER']->isAuthorized();
	}

	public function getItems(array $ids): array
	{
		return [];
	}

	public function getSelectedItems(array $ids): array
	{
		return $this->getTaskItems(['ids' => $ids]);
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$dialog->addItems(
			$this->getTaskItems(['searchQuery' => $searchQuery->getQuery()])
		);
	}

	public function fillDialog(Dialog $dialog): void
	{
		$this->fillWithRecentItems($dialog);

		if ($dialog->getItemCollection()->count() < self::$maxCount)
		{
			$taskItems = $this->getTaskItems(['excludeIds' => $this->getRecentItemsIds($dialog)]);
			foreach ($taskItems as $item)
			{
				/** @var Item $item */
				$item->addTab('recents');
				$dialog->addItem($item);

				if ($dialog->getItemCollection()->count() >= self::$maxCount)
				{
					break;
				}
			}
		}
	}

	private function fillWithRecentItems(Dialog $dialog): void
	{
		if ($dialog->getRecentItems()->count() <= 0)
		{
			return;
		}

		$tasks = $this->getTasks(['ids' => $this->getRecentItemsIds($dialog)]);
		foreach ($dialog->getRecentItems()->getAll() as $item)
		{
			/** @var RecentItem $item */
			$itemId = $item->getId();

			if (
				!array_key_exists($itemId, $tasks)
				|| $dialog->getItemCollection()->get(self::$entityId, $itemId)
			)
			{
				continue;
			}

			$dialog->addItem(
				new Item([
					'entityId' => self::$entityId,
					'id' => $itemId,
					'title' => $tasks[$itemId],
					'tabs' => 'recents',
				])
			);

			if ($dialog->getItemCollection()->count() >= self::$maxCount)
			{
				break;
			}
		}
	}

	private function getRecentItemsIds(Dialog $dialog): array
	{
		$recentItems = $dialog->getRecentItems()->getAll();

		return array_map(
			static function (RecentItem $item) {
				return $item->getId();
			},
			$recentItems
		);
	}

	private function getTaskItems(array $options = []): array
	{
		return $this->makeTaskItems($this->getTasks($options), $options);
	}

	private function getTasks(array $options = []): array
	{
		$options = array_merge($this->getOptions(), $options);
		$tasks = [];

		$order = ['ID' => 'desc'];
		$filter = $this->getFilterByOptions($options);
		$parameters = [
			'USER_ID' => $GLOBALS['USER']->getId(),
			'NAV_PARAMS' => [
				'nTopCount' => self::$maxCount,
			],
		];
		$select = ['ID', 'TITLE'];

		$tasksResult = \CTasks::GetList($order, $filter, [], $parameters, $select);
		while ($task = $tasksResult->Fetch())
		{
			$tasks[$task['ID']] = Emoji::decode($task['TITLE']);
		}

		return $tasks;
	}

	private function getFilterByOptions(array $options): array
	{
		$filter = [];

		if (
			array_key_exists('searchQuery', $options)
			&& ($value = SearchIndex::prepareStringToSearch($options['searchQuery'])) !== ''
		)
		{
			$filter['*FULL_SEARCH_INDEX'] = $value;
		}

		if (
			array_key_exists('ids', $options)
			&& is_array($options['ids'])
			&& !empty($options['ids'])
		)
		{
			$filter['ID'] = $options['ids'];
		}

		if (
			array_key_exists('excludeIds', $options)
			&& is_array($options['excludeIds'])
			&& !empty($options['excludeIds'])
		)
		{
			$filter['!ID'] = $options['excludeIds'];
		}

		return $filter;
	}

	private function makeTaskItems(array $tasks, array $options = []): array
	{
		return self::makeItems($tasks, array_merge($this->getOptions(), $options));
	}

	private static function makeItems(array $tasks, array $options = []): array
	{
		$result = [];
		foreach ($tasks as $id => $title)
		{
			if ($title !== '')
			{
				$result[] = new Item([
					'entityId' => self::$entityId,
					'id' => $id,
					'title' => $title,
					'tabs' => 'recents',
				]);
			}
		}

		return $result;
	}
}