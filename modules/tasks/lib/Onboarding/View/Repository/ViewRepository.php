<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\View\Repository;

use Bitrix\Main\Type\Collection;
use Bitrix\Tasks\Internals\Task\ViewedTable;
use Bitrix\Tasks\Onboarding\View\ViewRepositoryInterface;

class ViewRepository implements ViewRepositoryInterface
{
	protected static array $views = [];

	public function isViewed(int $taskId, int $userId): bool
	{
		if (isset(static::$views[$taskId]))
		{
			return in_array($userId, static::$views[$taskId], true);
		}

		static::$views[$taskId] = [];

		$item = ViewedTable::query()
			->setSelect(['USER_ID'])
			->where('TASK_ID', $taskId)
			->where('IS_REAL_VIEW', true)
			->fetchAll();

		$userIds = array_column($item, 'USER_ID');

		Collection::normalizeArrayValuesByInt($userIds, false);

		static::$views[$taskId] = $userIds;

		return in_array($userId, static::$views[$taskId], true);
	}
}