<?php

class CIntranetTime
{
	protected static $instance;

	const DATE_TIME_FORMAT = 'Y-m-d H:i:s';
	const SECONDS_PER_MINUTE = 60;
	const MINUTES_PER_HOUR = 60;
	const SECONDS_PER_HOUR = 3600;
	const SECONDS_PER_DAY = 86400;

	public static function getInstance(): self
	{
		if (!static::$instance)
		{
			static::$instance = new static();
		}
		return static::$instance;
	}

	public function getUserUtcOffset(int $userId): int
	{
		$userId = (int)$userId;

		static $usersUtcOffsets = [];
		if (!isset($usersUtcOffsets[$userId]))
		{
			$dateTimeServer = new \DateTime(
				'now',
				$this->createTimezoneByOffset($this->getServerUtcOffset())
			);

			$usersUtcOffsets[$userId] = $dateTimeServer->getOffset() + $this->getUserToServerOffset($userId);
		}

		return $usersUtcOffsets[$userId];
	}

	public function createTimezoneByOffset($offsetSeconds): \DateTimeZone
	{
		$offsetSeconds = (int)$offsetSeconds;

		static $timezonesByOffset = [];
		if (!isset($timezonesByOffset[$offsetSeconds]))
		{
			$timezonesByOffset[$offsetSeconds] = new \DateTimeZone($this->getFormattedOffset($offsetSeconds));
		}

		return $timezonesByOffset[$offsetSeconds];
	}

	private function getUserToServerOffset(int $userId): int
	{
		static $timezoneOffsets = [];

		if (!isset($timezoneOffsets[$userId]))
		{
			$timezoneOffsets[$userId] = (int)\CTimeZone::getOffset($userId, true);
		}

		return $timezoneOffsets[$userId];
	}

	private function getServerUtcOffset(): int
	{
		return (int)date('Z');
	}

	private function getFormattedOffset($offsetSeconds, $leadingHourZero = true)
	{
		static $timezonesByOffset = [];
		if (!isset($timezonesByOffset[$offsetSeconds]))
		{
			$gmtOffset = $offsetSeconds > 0 ? '+' : '-';
			$res = $gmtOffset . $this->convertSecondsToHoursMinutes(abs($offsetSeconds), $leadingHourZero);

			$timezonesByOffset[$offsetSeconds] = $res;
		}

		return $timezonesByOffset[$offsetSeconds];
	}

	private function convertSecondsToHoursMinutes($seconds, $leadingHourZero = true)
	{
		if ($seconds === null)
		{
			return null;
		}

		return (
			$leadingHourZero ?
				str_pad($this->getHours($seconds), 2, 0, STR_PAD_LEFT)
				: $this->getHours($seconds)) . ':' . str_pad($this->getMinutes($seconds), 2, 0, STR_PAD_LEFT
			);
	}

	private function getMinutes($secs): int
	{
		return (int)(($secs % self::SECONDS_PER_HOUR) / self::SECONDS_PER_MINUTE);
	}

	private function getSeconds($secs): int
	{
		return (int)(($secs % self::SECONDS_PER_HOUR % self::MINUTES_PER_HOUR));
	}

	private function getHours($secs): int
	{
		return (int)($secs / self::SECONDS_PER_HOUR);
	}
}