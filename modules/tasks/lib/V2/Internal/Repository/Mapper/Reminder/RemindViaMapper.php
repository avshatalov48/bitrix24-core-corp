<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper\Reminder;

use Bitrix\Tasks\Internals\Task\ReminderTable;
use Bitrix\Tasks\V2\Internal\Entity\Task\Reminder\RemindVia;

class RemindViaMapper
{
	public function mapFromEnum(RemindVia $remindVia): ?string
	{
		return match($remindVia)
		{
			RemindVia::Notification => ReminderTable::TRANSPORT_JABBER,
			RemindVia::Email => ReminderTable::TRANSPORT_EMAIL,
			default => null,
		};
	}

	public function mapToEnum(string $remindVia): ?RemindVia
	{
		return match($remindVia)
		{
			ReminderTable::TRANSPORT_JABBER => RemindVia::Notification,
			ReminderTable::TRANSPORT_EMAIL => RemindVia::Email,
			default => null,
		};
	}
}