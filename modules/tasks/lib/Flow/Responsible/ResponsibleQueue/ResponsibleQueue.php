<?php

namespace Bitrix\Tasks\Flow\Responsible\ResponsibleQueue;

final class ResponsibleQueue
{
	public function __construct(private int $flowId)
	{}

	public function getFlowId(): int
	{
		return $this->flowId;
	}

	/** @var array<ResponsibleQueueItem> $items */
	private array $items = [];

	public function addItem(ResponsibleQueueItem $item): self
	{
		if ($item->getFlowId() === $this->flowId)
		{
			$this->items[] = $item;
		}

		return $this;
	}

	/**
	 * @return array<ResponsibleQueueItem>
	 */
	public function getItems(): array
	{
		return $this->items;
	}

	public function getUserIds(): array
	{
		return array_map(fn(ResponsibleQueueItem $item) => $item->getUserId(), $this->items);
	}

	public function getUserIdsAfterSpecificUser(int $userId): array
	{
		$userIds = $this->getUserIds();

		$splitIndex = array_search($userId, $userIds, true);

		if ($splitIndex !== false)
		{
			$before = array_slice($userIds, 0, $splitIndex);
			$after = array_slice($userIds, $splitIndex + 1);

			$userIds = [...$after, ...$before];
		}

		return $userIds;
	}
}
