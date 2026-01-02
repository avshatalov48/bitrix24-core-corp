<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Tasks\Internals\UserOption\Task;

class UpdateUserOptions
{
	public function __invoke(array $fields, array $sourceTaskData): void
	{
		Task::onTaskUpdate($sourceTaskData, $fields);
	}
}