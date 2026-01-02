<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\V2\Internal\Entity\Task\Reminder;
use Bitrix\Tasks\V2\Internal\Entity\Task\ReminderCollection;

interface ReminderRepositoryInterface
{
	public function save(Reminder $reminder): int;

	public function saveBatch(ReminderCollection $reminders): void;

	public function deleteByFilter(array $filter): void;
}