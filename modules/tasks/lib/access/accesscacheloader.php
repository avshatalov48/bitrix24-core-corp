<?php

namespace Bitrix\Tasks\Access;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Internals\Registry\FeaturePermRegistry;
use Bitrix\Tasks\Internals\Registry\TagRegistry;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;

class AccessCacheLoader
{
	public function preload(int $userId, array $taskIds)
	{
		$registry = TaskRegistry::getInstance();

		$registry->load($taskIds, true);

		if (!Loader::includeModule('socialnetwork'))
		{
			return;
		}

		$groupIds = [];
		foreach ($taskIds as $id)
		{
			$task = $registry->get($id);
			if (
				!$task
				|| !$task['GROUP_ID']
			)
			{
				continue;
			}
			$groupIds[] = (int) $task['GROUP_ID'];
		}

		if (empty($groupIds))
		{
			return;
		}

		$sonetRegistry = FeaturePermRegistry::getInstance();
		$sonetRegistry->load($groupIds, 'tasks', 'edit_tasks', $userId);
		$sonetRegistry->load($groupIds, 'tasks', 'create_tasks', $userId);
		$sonetRegistry->load($groupIds, 'tasks', 'delete_tasks', $userId);
		$sonetRegistry->load($groupIds, 'tasks', 'view_all', $userId);
	}

	public function preloadTags(array $tagsIds): void
	{
		$registry = TagRegistry::getInstance();
		$registry->load($tagsIds);
	}

}