<?php

namespace Bitrix\Tasks\Integration\Calendar;

use Bitrix\Tasks\Util\Type\DateTime;

interface ClosestWorkDateStrategyInterface
{
	public function getClosestWorkDate(\Bitrix\Main\Type\DateTime $date, int $offsetInSeconds): DateTime;
}
