<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Task\Reminder;

enum RemindVia: string
{
	case Notification = 'notification';
	case Email = 'email';
}