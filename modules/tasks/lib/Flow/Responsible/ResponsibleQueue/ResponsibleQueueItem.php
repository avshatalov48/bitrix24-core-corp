<?php

namespace Bitrix\Tasks\Flow\Responsible\ResponsibleQueue;

use Bitrix\Main\Type\Contract\Arrayable;

final class ResponsibleQueueItem implements Arrayable
{
	public function __construct(
		private int $id,
		private int $flowId,
		private int $userId,
		private int $nextUserId,
		private int $sort,
	)
	{}

	public function getId(): int
	{
		return $this->id;
	}

	public function getFlowId(): int
	{
		return $this->flowId;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getNextUserId(): int
	{
		return $this->nextUserId;
	}

	public function getSort(): int
	{
		return $this->sort;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'flowId' => $this->flowId,
			'userId' => $this->userId,
			'nextUserId' => $this->nextUserId,
			'sort' => $this->sort,
		];
	}
}
