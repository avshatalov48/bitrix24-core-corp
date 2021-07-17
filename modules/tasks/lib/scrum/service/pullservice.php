<?php

namespace Bitrix\Tasks\Scrum\Service;

class PullService
{
	private $groupId;
	private $subscribers = [];

	public function __construct(int $groupId)
	{
		$this->groupId = $groupId;
	}

	public function addSubscriber(int $userId)
	{
		$this->subscribers[] = $userId;
	}

	public function subscribeToEntityActions(): void
	{
		$tag = 'entityActions_' . $this->groupId;

		foreach ($this->subscribers as $userId)
		{
			\CPullWatch::add($userId, $tag);
		}
	}

	public function subscribeToItemActions(): void
	{
		$tag = 'itemActions_' . $this->groupId;

		foreach ($this->subscribers as $userId)
		{
			\CPullWatch::add($userId, $tag);
		}
	}
}
