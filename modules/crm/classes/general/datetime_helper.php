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
		if(strpos(CSite::GetTimeFormat(), 'SS') !== false
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

	public static function GetMaxDatabaseDate()
	{
		global $DBType;
		$dbType = strtoupper($DBType);
		if($dbType === 'MYSQL')
		{
			return "'9999-12-31 00:00:00'";
		}
		elseif($dbType === 'MSSQL')
		{
			return "CONVERT(DATETIME, '9999-12-31 00:00:00', 121)";
		}
		elseif($dbType === 'ORACLE')
		{
			return "TO_DATE('9999-12-31 00:00:00', 'YYYY-MM-DD HH24:MI:SS')";
		}
		return "'9999-12-31 00:00:00'";
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
}