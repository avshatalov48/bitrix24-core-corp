<?php

namespace Bitrix\Booking\Internals\Service;

use DateTimeImmutable;

//@todo find a better place for this class
class Time
{
	public const HOURS_IN_DAY = 24;
	public const MINUTES_IN_HOUR = 60;
	public const SECONDS_IN_MINUTE = 60;
	public const SECONDS_IN_HOUR = 3600;
	public const SECONDS_IN_DAY = 86400;
	public const MINUTES_IN_DAY = 1440;
	public const DAYS_IN_YEAR = 365;
	public const DAYTIME_START_HOUR = 8;
	public const DAYTIME_END_HOUR = 21;

	public static function getSecondsFromMidnight(DateTimeImmutable $dateTime): int
	{
		return (
			(int)$dateTime->format('H') * self::SECONDS_IN_HOUR
			+ (int)$dateTime->format('i') * self::SECONDS_IN_MINUTE
			+ (int)$dateTime->format('s')
		);
	}

	public static function getMinutesFromMidnight(DateTimeImmutable $dateTime): int
	{
		return self::getSecondsFromMidnight($dateTime) / self::MINUTES_IN_HOUR;
	}

	public static function getDayCode(\DateTimeInterface $dateTime): string
	{
		$map = [
			'SU',
			'MO',
			'TU',
			'WE',
			'TH',
			'FR',
			'SA',
			'SU',
		];

		return $map[(int)$dateTime->format('w')];
	}
}
