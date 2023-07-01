<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder\DeadlineBased\DateFilters;

use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Main\Type\DateTime;
use CCrmDateTimeHelper;

final class Dates {

	public static function now(QueryParams $params): DateTime
	{
		return new DateTime();
	}

	public static function endOfCurrentDay(QueryParams $params): DateTime
	{
		$date = DateTime::createFromTimestamp((new DateTime())->getTimestamp());
		$date->setTime(23, 59, 59);
		return CCrmDateTimeHelper::getServerTime($date,$params->userIds()[0] ?? null)->disableUserTime();
	}

	public static function beginOfCurrentDay(QueryParams $params): DateTime
	{
		$date = DateTime::createFromTimestamp((new DateTime())->getTimestamp());
		$date->setTime(00, 00, 0);
		return CCrmDateTimeHelper::getServerTime($date,$params->userIds()[0] ?? null)->disableUserTime();
	}

}
