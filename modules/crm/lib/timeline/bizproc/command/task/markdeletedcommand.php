<?php

namespace Bitrix\Crm\Timeline\Bizproc\Command\Task;

use Bitrix\Crm\Service\Timeline\Item\Activity\Bizproc;

final class MarkDeletedCommand extends MarkCompletedCommand
{
	protected string $status = Bizproc\Task::TASK_STATUS_DELETED;

	protected function canChangeStatus(string $status): bool
	{
		return $status !== Bizproc\Task::TASK_STATUS_DELEGATED;
	}
}
