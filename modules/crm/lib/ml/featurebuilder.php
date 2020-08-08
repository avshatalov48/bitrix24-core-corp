<?php

namespace Bitrix\Crm\Ml;

use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
class FeatureBuilder
{
	/**
	 * @param array $activities
	 * @param DateTime $from
	 * @param DateTime $to
	 * @return array
	 */
	public static function filterActivitiesByDate(array $activities, DateTime $from, DateTime $to)
	{
		$fromTimestamp = $from->getTimestamp();
		$toTimestamp = $to->getTimestamp();
		$filter = function($act) use($fromTimestamp, $toTimestamp)
		{
			$startDate = $act["START_TIME"] ?: $act["CREATED"];
			if(!($startDate instanceof DateTime))
			{
				return false;
			}
			$startTimestamp = $startDate->getTimestamp();
			return $startTimestamp >= $fromTimestamp && $startTimestamp <= $toTimestamp;
		};

		return array_filter($activities, $filter);
	}

	/**
	 * Returns median array value.
	 *
	 * @param int[] $values Sorted array of integer values
	 * @return integer
	 */
	public static function getMedianValue(array $values)
	{
		$count = count($values);
		if($count == 0)
		{
			return 0;
		}
		else if($count == 1)
		{
			return $values[1];
		}
		else
		{
			if($count % 2)
			{
				$m = $count / 2;
				return intval(($values[$m - 1] + $values[$m + 1]) / 2);
			}
			else
			{
				$m = ($count + 1) / 2;
				return $values[$m] + $values[$count + 1];
			}
		}
	}

	/**
	 * @param string $input
	 * @param int $maxWords
	 *
	 * @return string
	 */
	public static function clearText($input, $maxWords = 0)
	{
		//$input = Encoding::convertEncoding($input, "utf8", "cp1251");
		$result = HTMLToTxt($input);

		// strip BBCode
		$result = preg_replace('/[[\/\!]*?[^\[\]]*?]/si', ' ', $result);

		// strip punctuation
		$result = preg_replace("/[[:punct:]]/", ' ', $result);

		// replace multiple spaces with single one
		$result = preg_replace("/[[:space:]]+/", ' ', $result);

		// remove short words
		$words = explode(" ", $result);
		$words = array_filter($words, function($word) {return mb_strlen($word) > 3 && \Bitrix\Main\Text\UtfSafeString::checkEncoding($word);});

		if($maxWords > 0)
		{
			$words = array_slice($words, 0, $maxWords);
		}

		return join(" ", $words);
	}

	public static function getDayOfWeek(Date $date)
	{
		return $date->format("N");
	}

	public static function getMonth(Date $date)
	{
		return $date->format("M");
	}

	public static function getTimeMnemonic(DateTime $dateTime)
	{
		$hour = $dateTime->format("G");

		if($hour <= 6)
		{
			return "night";
		}
		else if ($hour <= 11)
		{
			return "morning";
		}
		else if ($hour <= 18)
		{
			return "day";
		}
		else if ($hour <= 22)
		{
			return "evening";
		}
		else
		{
			return "night";
		}
	}
}