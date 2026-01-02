<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Provider;

use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Public\Provider\Params\TaskParams;

class TaskProvider extends AbstractTaskProvider
{
	public function get(TaskParams $taskParams): ?Task
	{
		return $this->getById($taskParams);
	}
}
