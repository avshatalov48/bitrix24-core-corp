<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper\Reminder;

use Bitrix\Tasks\Internals\Task\ReminderTable;
use Bitrix\Tasks\V2\Internal\Entity\Task\Reminder\RemindBy;

class RemindByMapper
{
	public function mapFromEnum(RemindBy $remindBy): ?string
	{
		return match($remindBy)
		{
			RemindBy::Date => ReminderTable::TYPE_COMMON,
			RemindBy::Deadline => ReminderTable::TYPE_DEADLINE,
			RemindBy::Recurring => ReminderTable::TYPE_RECURRING,
			default => null,
		};
	}

	public function mapToEnum(string $remindBy): ?RemindBy
	{
		return match($remindBy)
		{
			ReminderTable::TYPE_COMMON => RemindBy::Date,
			ReminderTable::TYPE_DEADLINE => RemindBy::Deadline,
			ReminderTable::TYPE_RECURRING => RemindBy::Recurring,
			default => null,
		};
	}
}