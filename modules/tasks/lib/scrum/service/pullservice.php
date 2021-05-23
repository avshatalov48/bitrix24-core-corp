<?php

namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Loader;

class PullService
{
	private $groupId;
	private $subscribers = [];

	private $isModuleIncluded;

	public function __construct(int $groupId)
	{
		$this->groupId = $groupId;

		$this->isModuleIncluded = (Loader::includeModule('pull'));
	}

	public function addSubscriber(int $userId)
	{
		$this->subscribers[] = $userId;
	}

	public function subscribeToEntityActions(): void
	{
		if (!$this->isModuleIncluded)
		{
			return;
		}

		$tag = 'entityActions_' . $this->groupId;

		foreach ($this->subscribers as $userId)
		{
			\CPullWatch::add($userId, $tag);
		}
	}

	public function subscribeToItemActions(): void
	{
		if (!$this->isModuleIncluded)
		{
			return;
		}

		$tag = 'itemActions_' . $this->groupId;

		foreach ($this->subscribers as $userId)
		{
			\CPullWatch::add($userId, $tag);
		}
	}
}