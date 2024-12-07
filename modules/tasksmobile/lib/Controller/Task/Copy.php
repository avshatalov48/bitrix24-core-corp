<?php

namespace Bitrix\TasksMobile\Controller\Task;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\TasksMobile\Controller\Base;
use Bitrix\TasksMobile\Exception\PermissionCheckFailedException;
use Bitrix\TasksMobile\Provider\ChecklistProvider;

final class Copy extends Base
{
	protected function getQueryActionNames(): array
	{
		return [
			'getSourceTaskData',
		];
	}

	public function getSourceTaskDataAction(int $taskId): array
	{
		$userId = CurrentUser::get()->getId() ?? 0;

		$this->assertTaskReadPermission($taskId, $userId);

		return $this->convertKeysToCamelCase([
			'CHECKLIST' => (new ChecklistProvider())->getChecklistTree($taskId, true),
		]);
	}

	/**
	 * @throws PermissionCheckFailedException
	 */
	private function assertTaskReadPermission(int $taskId, int $userId): void
	{
		$result = TaskAccessController::can(
			$userId,
			ActionDictionary::ACTION_TASK_READ,
			$taskId
		);

		if (!$result)
		{
			throw new PermissionCheckFailedException('Task is not accessible');
		}
	}
}
