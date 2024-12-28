<?php

namespace Bitrix\Tasks\Integration\UI\EntitySelector;

use Bitrix\Main\Text\Emoji;
use Bitrix\Tasks\Internals\Task\MetaStatus;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Provider\TaskList;
use Bitrix\Tasks\Provider\TaskQuery;
use Bitrix\Tasks\Util\User;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;

class TaskWithIdProvider extends TaskProvider
{
	protected static $entityId = 'task-with-id';
	protected static $maxCount = 15;

	public function fillDialog(Dialog $dialog): void
	{
		$this->fillWithRecentItems($dialog);

		if ($dialog->getItemCollection()->count() < static::$maxCount)
		{
			$taskItems = $this->getTaskItems([
				'excludeIds' => array_unique(
					array_merge($this->getRecentItemsIds($dialog), $this->getExcludeIds($dialog))
				),
				'parentId' => $this->getParentId($dialog),
				'doer' => User::getId(),
				'statuses' => [
					MetaStatus::UNSEEN,
					MetaStatus::EXPIRED,
					Status::NEW,
					Status::PENDING,
					Status::IN_PROGRESS,
				],
			]);
			foreach ($taskItems as $item)
			{
				/** @var Item $item */
				$item->addTab('recents');
				$dialog->addItem($item);

				if ($dialog->getItemCollection()->count() >= static::$maxCount)
				{
					break;
				}
			}
		}
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$searchQuery->setCacheable(false);

		parent::doSearch($searchQuery, $dialog);
	}

	protected function getTasks(array $options = []): array
	{
		$options = array_merge($this->getOptions(), $options);
		$tasks = [];

		$order = ['STATUS' => 'ASC', 'DEADLINE' => 'DESC', 'PRIORITY' => 'DESC', 'ID' => 'DESC'];
		$filter = $this->getFilterByOptions($options);

		$select = ['ID', 'TITLE', 'STATUS'];

		$taskQuery =
			(new TaskQuery(User::getId()))
				->setOrder($order)
				->setWhere($filter)
				->setSelect($select)
				->setLimit(static::$maxCount)
		;
		$taskList = (new TaskList())->getList($taskQuery);
		foreach ($taskList as $task)
		{
			$task['TITLE'] = $task['TITLE'] . "[{$task['ID']}]";

			$tasks[$task['ID']] = $task;
		}

		return $tasks;
	}

	protected function fillSearchFilter(array &$filter, string $searchQuery): void
	{
		if ($searchQuery === '')
		{
			return;
		}

		$filter['META::ID_OR_NAME'] = $searchQuery;
	}

	protected static function getSupertitleByStatus(int $status): string
	{
		return ($status === Status::COMPLETED) ? Status::getMessage($status) : '';
	}
}
