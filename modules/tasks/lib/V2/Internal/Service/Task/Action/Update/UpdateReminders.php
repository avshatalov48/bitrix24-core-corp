<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Tasks\V2\Internal\DI\Container;

class UpdateReminders
{
	public function __invoke(array $fullTaskData, array $changes): void
	{
		if (!isset($changes['DEADLINE']) || !is_array($changes['DEADLINE']))
		{
			return;
		}

		$deadline = $changes['DEADLINE'];

		$from = $deadline['FROM_VALUE'] ?? null;
		$to = $deadline['TO_VALUE'] ?? null;

		$from = $from === false ? null : $from;
		$to = $to === false ? null : $to;

		if ($from === $to)
		{
			return;
		}

		if ($from !== null && $to === null)
		{
			Container::getInstance()->getReminderRepository()->deleteByFilter(['=TASK_ID' => $fullTaskData['ID']]);

			return;
		}

		if ($from !== null && $to !== null)
		{
			Container::getInstance()->getReminderService()->recalculateDeadlineRemindersByTaskId($fullTaskData['ID'], $to);
		}
	}
}