<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Task;

// 'secs','mins','hours','days','weeks','monts','years'
enum Duration: string
{
	case Seconds = 'seconds';
	case Minutes = 'minutes';
	case Hours = 'hours';
	case Days = 'days';
	case Weeks = 'weeks';
	case Months = 'months';
	case Years = 'years';
}