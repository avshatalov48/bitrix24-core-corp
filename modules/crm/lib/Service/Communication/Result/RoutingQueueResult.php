<?php

namespace Bitrix\Crm\Service\Communication\Result;

use Bitrix\Crm\Service\Communication\Channel\Queue\Queue;
use Bitrix\Main\Result;

final class RoutingQueueResult extends Result
{
	private int $index = 0;

	public function setQueue(Queue $queue): self
	{
		$this->data['queue'] = $queue;

		return $this;
	}

	public function getQueue(): ?Queue
	{
		return $this->data['queue'] ?? null;
	}

	public function setUserIds(array $userIds): self
	{
		$this->data['userIds'] = $userIds;

		return $this;
	}

	public function getNextUserId(): ?int
	{
		return $this->data['userIds'][++$this->index] ?? null;
	}
}
