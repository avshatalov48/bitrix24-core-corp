<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Integration\Calendar;

use Bitrix\Tasks\Util\Type\DateTime;

trait NextWorkDateTrait
{
	protected function getNextWorkDate(\Bitrix\Main\Type\DateTime $date): DateTime
	{
		$shiftStart = $this->schedule->getShiftStart($date);
		if ($date->getTimestamp() < $shiftStart->getTimestamp())
		{
			$date = $shiftStart;
		}

		$shiftEnd = $this->schedule->getShiftEnd($date);
		if ($date->getTimestamp() > $shiftEnd->getTimestamp())
		{
			$date = $shiftStart->add('1 day');
		}

		return $date;
	}
}
