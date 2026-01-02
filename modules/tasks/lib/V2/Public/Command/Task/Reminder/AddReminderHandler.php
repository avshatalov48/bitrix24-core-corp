<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Reminder;

use Bitrix\Tasks\V2\Internal\Entity\Task\Reminder;
use Bitrix\Tasks\V2\Internal\Service\Task\ReminderService;

class AddReminderHandler
{
	public function __construct(
		private readonly ReminderService $reminderService,
	)
	{

	}

	public function __invoke(AddReminderCommand $command): Reminder
	{
		$id = $this->reminderService->add($command->reminder);

		return $command->reminder->cloneWith(['id' => $id]);
	}
}