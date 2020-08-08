<?php
namespace Bitrix\Tasks\Grid\Row\Content;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Grid\Row\Content;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Type\DateTime;
use CSite;
use CTimeZone;

/**
 * Class Date
 *
 * @package Bitrix\Tasks\Grid\Row\Content
 */
class Date extends Content
{
	/**
	 * @param $date
	 * @return string
	 */
	public static function formatDate($date): string
	{
		if (!$date)
		{
			return Loc::getMessage('TASKS_GRID_ROW_CONTENT_DATE_NOT_PRESENT');
		}

		$timestamp = static::getDateTimestamp($date);
		$format = static::getDateTimeFormat($timestamp);

		return UI::formatDateTime($timestamp, $format);
	}

	/**
	 * @param int $timestamp
	 * @return bool
	 */
	public static function isExpired(int $timestamp): bool
	{
		return $timestamp && ($timestamp <= static::getNow());
	}

	/**
	 * @return int
	 */
	protected static function getNow(): int
	{
		return (new DateTime())->getTimestamp() + CTimeZone::GetOffset();
	}

	/**
	 * @param string $date
	 * @return int
	 */
	protected static function getDateTimestamp($date): int
	{
		$timestamp = MakeTimeStamp($date);

		if ($timestamp === false)
		{
			$timestamp = strtotime($date);
			if ($timestamp !== false)
			{
				$timestamp += CTimeZone::GetOffset() - DateTime::createFromTimestamp($timestamp)->getSecondGmt();
			}
		}

		return $timestamp;
	}

	protected static function getDateTimeFormat(int $timestamp): string
	{
		$dateFormat = static::getDateFormat($timestamp);
		$timeFormat = static::getTimeFormat($timestamp);

		return $dateFormat.($timeFormat ? ', '.$timeFormat : '');
	}

	/**
	 * @param int $timestamp
	 * @return string
	 */
	protected static function getDateFormat(int $timestamp): string
	{
		$dateFormat = 'j F';

		if (LANGUAGE_ID === 'en')
		{
			$dateFormat = "F j";
		}
		else if (LANGUAGE_ID === 'de')
		{
			$dateFormat = "j. F";
		}

		if (date('Y') !== date('Y', $timestamp))
		{
			if (LANGUAGE_ID === 'en')
			{
				$dateFormat .= ",";
			}

			$dateFormat .= ' Y';
		}

		return $dateFormat;
	}

	protected static function getTimeFormat(int $timestamp): string
	{
		$timeFormat = '';
		$currentTimeFormat = 'HH:MI:SS';

		$resSite = CSite::GetByID(SITE_ID);
		if ($site = $resSite->Fetch())
		{
			$currentTimeFormat = str_replace($site['FORMAT_DATE'].' ', '', $site['FORMAT_DATETIME']);
		}

		if (date('Hi', $timestamp) > 0)
		{
			$timeFormat = ($currentTimeFormat === 'HH:MI:SS' ? 'G:i' : 'g:i a');
		}

		return $timeFormat;
	}
}