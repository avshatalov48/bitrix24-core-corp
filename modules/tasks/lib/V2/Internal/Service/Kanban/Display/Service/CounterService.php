<?php

namespace Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service;

class CounterService
{
	private int $userId;
	private int $viewedUserId;

	public function __construct(int $userId, int $viewedUserId)
	{
		$this->userId = $userId;
		$this->viewedUserId = $viewedUserId;
	}

	/**
	 * Fill data-array with counters.
	 * @param array $items Task items.
	 * @return array
	 */
	public function getCounters(array $items): array
	{
		if (
			$this->userId !== $this->viewedUserId
			&& !\Bitrix\Tasks\Util\User::isAdmin($this->userId)
			&& !\CTasks::IsSubordinate($this->viewedUserId, $this->userId)
		)
		{
			return $items;
		}

		foreach ($items as $taskId => $row)
		{
			$rowCounter = (new \Bitrix\Tasks\Internals\Counter\Template\TaskCounter($this->viewedUserId))->getRowCounter($taskId);
			if (!$rowCounter['VALUE'])
			{
				$items[$taskId]['data']['counter'] = 0;
				continue;
			}
			$items[$taskId]['data']['counter'] = [
				'value' => $rowCounter['VALUE'],
				'color' => "ui-counter-{$rowCounter['COLOR']}",
			];
			$items[$taskId]['data']['count_comments'] = $rowCounter['VALUE'];
		}

		return $items;
	}
}