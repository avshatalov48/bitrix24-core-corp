<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity;

class InMemoryUserOptionRepository implements UserOptionRepositoryInterface
{
	/**
	 * @var array<int, array<int, Entity\Task\UserOptionCollection>>
	 */
	private array $cache = [];

	public function __construct(
		private readonly UserOptionRepository $userOptionRepository,
	)
	{

	}

	public function get(int $taskId, int $userId): Entity\Task\UserOptionCollection
	{
		if (!isset($this->cache[$userId][$taskId]))
		{
			$this->cache[$userId][$taskId] = $this->userOptionRepository->get($taskId, $userId);
		}

		return $this->cache[$userId][$taskId];
	}

	public function isSet(int $code, int $taskId, int $userId): bool
	{
		if (!isset($this->cache[$userId][$taskId]))
		{
			$options = $this->userOptionRepository->get($taskId, $userId);
			$this->cache[$userId][$taskId] = $options;
		}

		return $this->cache[$userId][$taskId]->findOne(['code' => $code]) !== null;
	}

	public function add(Entity\Task\UserOption $userOption): void
	{
		$this->userOptionRepository->add($userOption);

		if (isset($this->cache[$userOption->userId][$userOption->taskId]))
		{
			unset($this->cache[$userOption->userId][$userOption->taskId]);
		}
	}

	public function delete(array $codes = [], int $taskId = 0, int $userId = 0): void
	{
		$this->userOptionRepository->delete($codes, $taskId, $userId);

		if ($taskId > 0)
		{
			unset($this->cache[$userId][$taskId]);
		}
		elseif ($userId > 0)
		{
			unset($this->cache[$userId]);
		}
	}
}