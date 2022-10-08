<?php

namespace Bitrix\Crm\Integration\Report\Handler\SalesDynamics;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;

class WonLostPrevious extends WonLostAmount
{
	const PRIMARY_WON = 'PREV_PRIMARY_WON';
	const PRIMARY_LOST = 'PREV_PRIMARY_LOST';
	const RETURN_WON = 'PREV_RETURN_WON';
	const RETURN_LOST = 'PREV_RETURN_LOST';
	const TOTAL_WON = 'PREV_TOTAL_WON';
	const TOTAL_LOST = 'PREV_TOTAL_LOST';

	public function addTimePeriodToQuery(Query $query, $timePeriodValue)
	{
		if ($timePeriodValue['from'] !== "" && $timePeriodValue['to'] !== "")
		{
			$from = new DateTime($timePeriodValue["from"]);
			$to = new DateTime($timePeriodValue["to"]);

			list($newFrom, $newTo) = static::getPreviousPeriod($from, $to);

			$query->whereBetween("CLOSEDATE", $newFrom, $newTo);
		}
	}
}
