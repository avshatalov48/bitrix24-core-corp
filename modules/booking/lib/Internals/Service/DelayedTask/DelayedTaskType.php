<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\DelayedTask;

enum DelayedTaskType: string
{
	case ResourceCalendarDataChanged = 'resourceCalendarDataChanged';
}
