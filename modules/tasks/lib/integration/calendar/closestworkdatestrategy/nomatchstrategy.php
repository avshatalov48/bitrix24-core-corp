<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Integration\Calendar\ClosestWorkDateStrategy;

use Bitrix\Tasks\Integration\Calendar\ClosestWorkDateStrategyInterface;
use Bitrix\Tasks\Integration\Calendar\NextWorkDateTrait;
use Bitrix\Tasks\Integration\Calendar\ScheduleInterface;
use Bitrix\Tasks\Util\Type\DateTime;

class NoMatchStrategy implements ClosestWorkDateStrategyInterface
{
	use NextWorkDateTrait;

	protected ScheduleInterface $schedule;

	public function __construct(ScheduleInterface $schedule)
	{
		$this->schedule = $schedule;
	}

	public function getClosestWorkDate(\Bitrix\Main\Type\DateTime $date, int $offsetInSeconds): DateTime
	{
		$date = DateTime::createFromDateTime($date);
		$date->add($offsetInSeconds . ' seconds');

		return $date;
	}
}
