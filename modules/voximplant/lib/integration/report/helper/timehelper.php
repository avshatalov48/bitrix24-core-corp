<?php

namespace Bitrix\Voximplant\Integration\Report\Helper;

use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

class TimeHelper
{
	public static function formatNumberWorkTime(array $numberWorkTimeFromDb): array
	{
		$workTimeFrom = self::convertNumberWorkTimeToUserDateTime(
			$numberWorkTimeFromDb['FROM'],
			$numberWorkTimeFromDb['TIMEZONE']
		);

		$workTimeTo = self::convertNumberWorkTimeToUserDateTime(
			$numberWorkTimeFromDb['TO'],
			$numberWorkTimeFromDb['TIMEZONE']
		);

		return [
			'FROM' => $workTimeFrom,
			'TO' => $workTimeTo,
		];
	}

	public static function convertNumberWorkTimeToUserDateTime(string $numberWorkTime, string $timeZone = ''): string
	{
		if (!mb_strpos($numberWorkTime, '.'))
		{
			$numberWorkTime .= '.00';
		}

		$date = (new Date())->format('Y-m-d');
		if ($timeZone !== '')
		{
			$datetime = new DateTime($date .' '. $numberWorkTime, 'Y-m-d H.i', new \DateTimeZone($timeZone));
		}
		else
		{
			$datetime = new DateTime($date .' '. $numberWorkTime, 'Y-m-d H.i');
		}

		return FormatDate('H.i', $datetime->toUserTime()->getTimestamp());
	}
}