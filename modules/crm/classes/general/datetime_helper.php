<?php
use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Entity\DatetimeField;
class CCrmDateTimeHelper
{
	public static function NormalizeDateTime($str)
	{
		// Add seconds if omitted
		if(mb_strpos(CSite::GetTimeFormat(), 'SS') !== false
			&& preg_match('/\d{1,2}\s*:\s*\d{1,2}\s*:\s*\d{1,2}/', $str) !== 1)
		{
			$str = preg_replace('/\d{1,2}\s*:\s*\d{1,2}/', '$0:00', $str);
		}

		return $str;
	}

	public static function AddOffset($datetime, $offset)
	{
		if(!is_int($offset))
		{
			$offset = (int)$offset;
		}

		if($offset === 0)
		{
			return $datetime;
		}

		return FormatDate('FULL', MakeTimeStamp($datetime, FORMAT_DATETIME) + $offset);
	}

	public static function SubtractOffset($datetime, $offset)
	{
		if(!is_int($offset))
		{
			$offset = (int)$offset;
		}

		if($offset === 0)
		{
			return $datetime;
		}

		return FormatDate('FULL', MakeTimeStamp($datetime, FORMAT_DATETIME) - $offset);
	}

	public static function GetMaxDatabaseDate($preparedForInsert = true)
	{
		$maxDate =self::getMaxDatabaseDateObject();
		if ($preparedForInsert)
		{
			return Main\Application::getConnection()->getSqlHelper()->convertToDbDateTime($maxDate);
		}

		return $maxDate->toString();
	}

	public static function getMaxDatabaseDateObject(): DateTime
	{
		return (new DateTime())
			->setDate(9999, 12, 31)
			->setTime(0, 0, 0)
			->disableUserTime()
		;
	}

	public static function IsMaxDatabaseDate($datetime, $format = false)
	{
		$parts = ParseDateTime($datetime, is_string($format) && $format !== '' ? $format : FORMAT_DATETIME);
		if(!is_array($parts))
		{
			return false;
		}

		$year = isset($parts['YYYY']) ? intval($parts['YYYY']) : 0;
		return $year === 9999;
	}
	public static function SetMaxDayTime($date)
	{
		if($date !== '')
		{
			try
			{
				$date = new DateTime($date, Date::convertFormatToPhp(FORMAT_DATE));
			}
			catch(Main\ObjectException $e)
			{
				try
				{
					$date = new DateTime($date, Date::convertFormatToPhp(FORMAT_DATETIME));
				}
				catch(Main\ObjectException $e)
				{
					$date = new DateTime();
				}
			}
		}
		else
		{
			$date = new DateTime();
		}
		$date->setTime(23, 59, 59);
		return $date->format(Date::convertFormatToPhp(FORMAT_DATETIME));
	}
	/**
	* Creates date object from string in format of current site
	* @return Bitrix\Main\Type\Date|null
	*/
	public static function ParseDateString($str)
	{
		if($str === '')
		{
			return null;
		}

		try
		{
			$date = new Date($str, Date::convertFormatToPhp(FORMAT_DATE));
		}
		catch(Main\ObjectException $e)
		{
			try
			{
				$date = new DateTime($str, Date::convertFormatToPhp(FORMAT_DATETIME));
				$date->setTime(0, 0, 0);
			}
			catch(Main\ObjectException $e)
			{
				return null;
			}
		}
		return $date;
	}
	public static function DateToSql(Date $date)
	{
		return Main\Application::getConnection()->getSqlHelper()->convertToDb($date, new DatetimeField('D'));
	}

	private static function getUserTimezoneOffset(int $userId = null): int
	{
		static $offsets = [];

		$currentUser = \Bitrix\Crm\Service\Container::getInstance()->getContext()->getUserId();
		if (is_null($userId))
		{
			$userId = $currentUser;
		}

		if (!isset($offsets[$userId]))
		{
			$offsets[$userId] = (int)($userId > 0 ? \CTimeZone::GetOffset($currentUser === $userId ? null : $userId) : 0);
		}

		return $offsets[$userId] ?: 0;
	}

	/**
	 * Coverts DateTime to user timezone for arbitrary user
	 *
	 * @param DateTime $serverTime
	 * @param int|null $userId
	 * @return DateTime
	 */
	public static function getUserTime(DateTime $serverTime, int $userId = null): DateTime
	{
		$offset = self::getUserTimezoneOffset($userId);
		$time = clone $serverTime;
		if ($offset)
		{
			$time->add(($offset < 0 ? '-' : '') . 'PT' . abs($offset) . 'S');
		}

		return $time;
	}

	/**
	 * Coverts DateTime from user timezone to server timezone for arbitrary user
	 *
	 * @param DateTime $userTime
	 * @param int|null $userId
	 * @return DateTime
	 */
	public static function getServerTime(DateTime $userTime, int $userId = null): DateTime
	{
		$offset = self::getUserTimezoneOffset($userId);
		$time = clone $userTime;
		if ($offset)
		{
			$time->add(($offset < 0 ? '' : '-') . 'PT' . abs($offset) . 'S');
		}

		return $time;
	}

	/**
	 * Coverts DateTime to Date according to user timezone for arbitrary user
	 *
	 * @param DateTime $serverDate
	 * @param int|null $userId
	 * @return DateTime
	 */
	public static function getUserDate(DateTime $serverDate, int $userId = null): Date
	{
		return Date::createFromTimestamp(
			\CCrmDateTimeHelper::getUserTime($serverDate, $userId)->setTime(0, 0, 0)->getTimestamp()
		);
	}
}
