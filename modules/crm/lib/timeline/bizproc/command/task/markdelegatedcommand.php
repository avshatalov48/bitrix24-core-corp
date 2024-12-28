<?php

namespace Bitrix\Crm\Timeline\Bizproc\Command\Task;

use Bitrix\Crm\Service\Timeline\Item\Activity\Bizproc;
use Bitrix\Crm\Activity\Provider\Bizproc\Task;

final class MarkDelegatedCommand extends MarkCompletedCommand
{
	protected string $status = Bizproc\Task::TASK_STATUS_DELEGATED;
}
