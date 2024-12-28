<?php

namespace Bitrix\Tasks\Flow\Grid\Preload;

use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Provider\TaskDirectorProvider;
use Bitrix\Tasks\Flow\Provider\UserProvider;
use Bitrix\Tasks\Internals\Log\Logger;

class TaskDirectorPreloader
{
	protected static array $storage = [];

	private UserProvider $userProvider;
	private TaskDirectorProvider $directorProvider;

	public function __construct()
	{
		$this->init();
	}

	final protected function load(array $filter, array $order, int ...$flowIds): void
	{
		if (empty($flowIds))
		{
			return;
		}

		$flowTaskDirectors = $this->getTasks($flowIds, $filter, $order);
		$directors = array_merge([], ...$flowTaskDirectors);
		try
		{
			$users = $this->userProvider->getUsersInfo($directors);
		}
		catch (SystemException $e)
		{
			Logger::logThrowable($e);
			return;
		}

		foreach ($flowTaskDirectors as $flowId => $userIds)
		{
			foreach ($userIds as $userId)
			{
				if (!isset($users[$userId]))
				{
					continue;
				}

				static::$storage[$flowId][] = $users[$userId];
			}
		}
	}

	final public function get(int $flowId): array
	{
		return static::$storage[$flowId] ?? [];
	}

	private function getTasks(array $flowIds, array $filter, array $order): array
	{
		$result = [];
		foreach ($flowIds as $flowId)
		{
			$result[$flowId] = $this->directorProvider->getDirectors($flowId, $filter, $order);
		}

		return $result;
	}

	private function init(): void
	{
		$this->userProvider = new UserProvider();
		$this->directorProvider = new TaskDirectorProvider();
	}
}