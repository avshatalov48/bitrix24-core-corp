<?php
namespace Bitrix\Timeman\Helper;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type;


class TimeHelper
{
	protected static $instance;
	private $dateFormat = false;

	/**
	 * @return static
	 */
	public static function getInstance()
	{
		if (!static::$instance)
		{
			static::$instance = new static();
		}
		return static::$instance;
	}

	public function getServerUtcOffset()
	{
		return date('Z');
	}

	public function getTimeRegExp()
	{
		$exp = '#^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]';
		if ($this->isAmPmMode())
		{
			$exp .= '[ apm]{0,3}';
		}
		return $exp . '$#';
	}

	public function convertSecondsToHoursMinutes($seconds, $leadingHourZero = true)
	{
		return ($leadingHourZero ? str_pad($this->getHours($seconds), 2, 0, STR_PAD_LEFT) : $this->getHours($seconds))
			   . ':' . str_pad($this->getMinutes($seconds), 2, 0, STR_PAD_LEFT);
	}

	public function convertSecondsToHoursMinutesPostfix($seconds)
	{
		$ts = $this->buildTimestampByFormattedDateForServer(ConvertTimeStamp()) + $seconds % 86400;
		return FormatDate($this->isAmPmMode() ? 'h:i a' : 'H:i', $ts);
	}

	public function convertHoursMinutesToSeconds($value)
	{
		if (!is_string($value))
		{
			return 0;
		}
		if (strlen($value) > 0)
		{
			list($hour, $min) = explode(':', $value, 2);

			if ($this->isAmPmMode() && preg_match('/(am|pm)/i', $min, $match))
			{
				$ampm = strtolower($match[0]);
				if ($ampm == 'pm' && $hour < 12)
				{
					$hour += 12;
				}
				elseif ($ampm == 'am' && $hour == 12)
				{
					$hour = 0;
				}
			}

			$value = abs($hour * 3600 + $min * 60);
			if ($value >= 86400)
			{
				return 86399;
			}
		}
		else
		{
			return 0;
		}
		return $value;
	}

	public function getUtcNowTimestamp()
	{
		return (int)gmdate('U');
	}

	public function getUtcTimestampForUserTime($userId, $daySeconds, $date = null)
	{
		$timeZone = $this->getUserTimezone($userId);
		if (!($timeZone instanceof \DateTimeZone))
		{
			return null;
		}
		if ($date === null)
		{
			$date = $this->getUserDateTimeNow($userId);
		}
		$dateFormatted = $date->format('Y-m-d');

		$seconds = str_pad($this->getSeconds($daySeconds), 2, '0', STR_PAD_LEFT);
		$dateTime = \DateTime::createFromFormat(
			'Y-m-d H:i:s',
			$dateFormatted . ' ' . $this->convertSecondsToHoursMinutes($daySeconds) . ':' . $seconds,
			$timeZone
		);
		if (!$dateTime)
		{
			return null;
		}
		return $dateTime->getTimestamp();
	}

	public function convertSecondsToHoursMinutesLocal($seconds, $keepZeroHours = true)
	{
		$sign = $seconds < 0 ? '-' : '';
		$seconds = abs($seconds);
		$hours = $this->getHours($seconds);
		$result = '';
		if ($keepZeroHours || $hours != 0)
		{
			$result = $sign . $hours . Loc::getMessage('JS_CORE_H') . ' ';
		};
		return $result . $this->getMinutes($seconds) . Loc::getMessage('JS_CORE_M');
	}

	public function getMinutes($secs)
	{
		return intval(($secs % TimeDictionary::SECONDS_PER_HOUR) / TimeDictionary::SECONDS_PER_MINUTE);
	}

	public function getSeconds($secs)
	{
		return intval(($secs % TimeDictionary::SECONDS_PER_HOUR % TimeDictionary::MINUTES_PER_HOUR));
	}

	public function getHours($secs)
	{
		return intval($secs / TimeDictionary::SECONDS_PER_HOUR);
	}

	public function convertUtcTimestampToDaySeconds($timestamp, $offset = 0)
	{
		return $this->getSecondsFromDateTime(
			$this->createDateTimeFromTimestamp($timestamp, $offset)
		);
	}

	private function createDateTimeFromTimestamp($timestamp, $offset = 0)
	{
		if ($offset instanceof \DateTimeZone)
		{
			$tz = $offset;
		}
		else
		{
			$tz = $this->createTimezoneByOffset($offset);
		}
		return $this->buildDateTimeAndSetTimezone('U', $timestamp, $tz);
	}

	public function convertUtcTimestampToHoursMinutesPostfix($timestamp, $offset = 0)
	{
		return $this->convertSecondsToHoursMinutesPostfix(
			$this->convertUtcTimestampToDaySeconds($timestamp, $offset)
		);
	}

	public function convertUtcTimestampToHoursMinutes($timestamp, $offset = 0)
	{
		return $this->convertSecondsToHoursMinutes(
			$this->convertUtcTimestampToDaySeconds($timestamp, $offset)
		);
	}

	/**
	 * @param \DateTime|Type\DateTime $dateTime
	 * @return int
	 */
	public function getSecondsFromDateTime($dateTime)
	{
		$parts = explode(':', $dateTime->format('G:i:s'));
		return (int)$parts[0] * TimeDictionary::SECONDS_PER_HOUR
			   + (int)$parts[1] * TimeDictionary::SECONDS_PER_MINUTE
			   + (int)$parts[2];
	}

	public function getFormattedOffset($offsetSeconds, $leadingHourZero = true)
	{
		static $formattedOffsets = [];
		if (!isset($formattedOffsets[$offsetSeconds]))
		{
			$gmtOffset = $offsetSeconds > 0 ? '+' : '-';
			$res = $gmtOffset . $this->convertSecondsToHoursMinutes(abs($offsetSeconds), $leadingHourZero);
			$formattedOffsets[$offsetSeconds] = $res;
		}
		return $formattedOffsets[$offsetSeconds];
	}

	public function getUserUtcOffset($userId)
	{
		$dateTimeServer = new \DateTime('now', $this->createTimezoneByOffset($this->getServerUtcOffset()));
		return $dateTimeServer->getOffset() + $this->getUserToServerOffset($userId);
	}

	public function getUserToServerOffset($userId = null)
	{
		return \CTimeZone::GetOffset($userId, true);
	}

	public function getUserDateTimeNow($userId)
	{
		$dateTime = $this->createDateTimeFromTimestamp($this->getUtcNowTimestamp());
		$dateTime->setTimezone($this->getUserTimezone($userId));
		return $dateTime;
	}

	public function getUserTimezone($userId)
	{
		$userOffset = $this->getUserUtcOffset($userId);
		return $this->createTimezoneByOffset($userOffset);
	}

	public function createDateTimeFromFormat($format, $dateString, $offset = 0)
	{
		return $this->buildDateTimeAndSetTimezone($format, $dateString, $this->createTimezoneByOffset($offset));
	}

	private function buildDateTimeAndSetTimezone($format, $dateString, $timezone)
	{
		$dateTime = \DateTime::createFromFormat(
			$format,
			$dateString
		);
		$dateTime->setTimezone($timezone);
		return $dateTime;
	}

	/**
	 * @param string $format
	 * @param string $dateString
	 * @param int $userId
	 * @return null|\DateTime
	 */
	public function createUserDateTimeFromFormat($format, $dateString, $userId)
	{
		if ($format === 'U')
		{
			return $dateString > 0 ? $this->buildDateTimeAndSetTimezone($format, $dateString, $this->getUserTimezone($userId)) : null;
		}
		else
		{
			$dateTime = \DateTime::createFromFormat(
				$format,
				$dateString,
				$this->getUserTimezone($userId)
			);
		}

		return $dateTime === false ? null : $dateTime;
	}

	public function getCurrentServerDateFormatted()
	{
		$date = $this->buildDateTimeAndSetTimezone(
			'U',
			$this->getUtcNowTimestamp(),
			new \DateTimeZone(date_default_timezone_get())
		);
		return $date->format('Y-m-d');
	}

	public function getTimestampByUserSecondsFromTimestamp($seconds, $initialTimestamp = null, $initialOffset = null)
	{
		if (is_null($seconds))
		{
			return null;
		}
		$userDateTime = $this->createDateTimeFromFormat('U', $initialTimestamp, $initialOffset);
		return $this->getTimestampOfTime($userDateTime, $seconds);
	}

	public function getTimestampByUserDate($formattedDate, $userId, $format = null)
	{
		$dateFormat = $this->getDateFormat();
		if ($format !== null)
		{
			$dateFormat = $format;
		}

		$ts = $this->buildTimestampByFormattedDateForServer($formattedDate, $dateFormat);
		return $ts > 0 ? $ts - $this->getUserToServerOffset($userId) : null;
	}

	public function buildTimestampByFormattedDateForServer($formattedDate, $dateFormat = false)
	{
		// utc timestamp, at the given date (and time 00:00) for the server
		return MakeTimeStamp($formattedDate, $dateFormat);
	}

	public function getTimestampByUserSeconds($userId, $seconds)
	{
		if (is_null($seconds))
		{
			return null;
		}
		$userDateTime = $this->getUserDateTimeNow($userId);
		return $this->getTimestampOfTime($userDateTime, $seconds);
	}

	/**
	 * @param \DateTime $dateTime
	 * @param $seconds
	 * @return mixed
	 */
	private function getTimestampOfTime($dateTime, $seconds)
	{
		$this->setTimeFromSeconds($dateTime, $seconds);
		return $dateTime->getTimestamp();
	}

	/**
	 * @param \DateTime $dateTime
	 * @param $seconds
	 */
	public function setTimeFromSeconds($dateTime, $seconds)
	{
		$dateTime->setTime($this->getHours($seconds), $this->getMinutes($seconds), $this->getSeconds($seconds));
	}

	private function createTimezoneByOffset($offset)
	{
		return new \DateTimeZone($this->getFormattedOffset($offset));
	}

	public function getDayOfWeek(\DateTime $dateTime)
	{
		return (int)$dateTime->format('N');
	}

	public function getDateFormat()
	{
		if ($this->dateFormat)
		{
			return $this->dateFormat;
		}
		return defined('FORMAT_DATE') ? FORMAT_DATE : false;
	}

	protected function isAmPmMode()
	{
		return IsAmPmMode();
	}
}