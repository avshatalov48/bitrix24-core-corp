<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Deadline;

enum SkipNotificationPeriod: string
{
	case DEFAULT = '';
	case DAY = 'day';
	case WEEK = 'week';
	case MONTH = 'month';
	case FOREVER = 'forever';
}
