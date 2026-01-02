<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Reminder;

use Bitrix\Tasks\V2\Internal\Entity\Task\Reminder;
use Bitrix\Tasks\V2\Internal\Repository\ReminderRepositoryInterface;

class UpdateReminderHandler
{
	public function __construct(
		private readonly ReminderRepositoryInterface $reminderRepository,
	)
	{

	}

	public function __invoke(UpdateReminderCommand $command): Reminder
	{
		$this->reminderRepository->save($command->reminder);

		return $command->reminder;
	}
}