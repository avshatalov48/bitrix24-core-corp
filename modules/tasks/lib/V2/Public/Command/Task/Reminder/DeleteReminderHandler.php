<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Reminder;

use Bitrix\Tasks\V2\Internal\Repository\ReminderRepositoryInterface;

class DeleteReminderHandler
{
	public function __construct(
		private readonly ReminderRepositoryInterface $reminderRepository,
	)
	{

	}

	public function __invoke(DeleteReminderCommand $command): void
	{
		$this->reminderRepository->deleteByFilter(['=ID' => $command->id]);
	}
}