<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Reminder;

enum ReminderAction: string
{
	case Read = 'reminder_read';
}