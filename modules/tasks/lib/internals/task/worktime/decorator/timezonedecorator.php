<?php

namespace Bitrix\Tasks\Internals\Task\WorkTime\Decorator;

use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;

class TimeZoneDecorator extends WorkTimeServiceDecorator
{
	private const SECONDS_IN_HOUR = 3600;

	public function getClosestWorkTime(int $offsetInDays = 7): DateTime
	{
		$offset = (int)(User::getTimeZoneOffset($this->source->userId) / static::SECONDS_IN_HOUR);

		return $this->source->getClosestWorkTime($offsetInDays)->add(-$offset . ' hours');
	}
}