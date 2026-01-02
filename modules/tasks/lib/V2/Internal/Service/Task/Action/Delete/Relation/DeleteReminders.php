<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation;

class DeleteReminders
{
	public function __invoke(array $fullTaskData): void
	{
		\CTaskReminders::DeleteByTaskID($fullTaskData['ID']);
	}
}