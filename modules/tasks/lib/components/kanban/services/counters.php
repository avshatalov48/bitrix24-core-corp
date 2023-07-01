<?php

namespace Bitrix\Tasks\Components\Kanban\Services;

class Counters
{
	private int $currentUserId;
	private int $viewedUserId;

	public function __construct(int $currentUserId, int $viewedUserId)
	{
		$this->currentUserId = $currentUserId;
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
			$this->currentUserId !== $this->viewedUserId
			&& !\Bitrix\Tasks\Util\User::isAdmin($this->currentUserId)
			&& !\CTasks::IsSubordinate($this->viewedUserId, $this->currentUserId)
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