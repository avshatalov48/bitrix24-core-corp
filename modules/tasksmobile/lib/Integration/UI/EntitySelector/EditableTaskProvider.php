<?php

namespace Bitrix\TasksMobile\Integration\UI\EntitySelector;

use Bitrix\Tasks\Integration\UI\EntitySelector\TaskProvider;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Access\AccessCacheLoader;

class EditableTaskProvider extends TaskProvider
{
	protected static $entityId = 'editable_task';

	protected function getTasks(array $options = []): array
	{
		$tasks = parent::getTasks($options);

		if (!empty($options['skipPermissionCheck']))
		{
			return $tasks;
		}

		$taskIds = array_keys($tasks);

		if (empty($taskIds))
		{
			return [];
		}

		$userId = User::getId();
		(new AccessCacheLoader())->preload($userId, $taskIds);
		$filteredTasks = [];

		foreach ($tasks as $taskId => $task)
		{
			if (TaskAccessController::can($userId, ActionDictionary::ACTION_TASK_EDIT, $taskId))
			{
				$filteredTasks[$taskId] = $task;
			}
		}

		return $filteredTasks;
	}

	public function getSelectedItems(array $ids): array
	{
		return $this->getTaskItems(['ids' => $ids, 'skipPermissionCheck' => true]);
	}
}
