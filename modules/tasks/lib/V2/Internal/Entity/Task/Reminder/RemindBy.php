<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Task\Reminder;

enum RemindBy: string
{
	case Deadline = 'deadline';
	case Date = 'date';
	case Recurring = 'recurring';
}