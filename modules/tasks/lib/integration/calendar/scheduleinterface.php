<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Integration\Calendar;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Util\Type;

interface ScheduleInterface
{
	public function getShiftStart(?DateTime $date = null): Type\DateTime;

	public function getShiftEnd(?DateTime $date = null): Type\DateTime;

	public function getWorkDayDuration(?DateTime $date = null): int;

	public function isWorkTime(DateTime $date): bool;

	public function isWeekend(DateTime $date): bool;
}