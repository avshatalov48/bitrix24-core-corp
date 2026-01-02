<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Enum\Notification;

use Bitrix\Booking\Entity\ValuesTrait;

enum ReminderNotificationDelay: int
{
	use ValuesTrait;

	case Morning = -1;
}
