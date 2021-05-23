<?php
namespace Bitrix\Timeman\Component\WorktimeGrid;

use \Bitrix\Main;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class Ranges
{

	public static function getPeriod($dateMin, $dateMax, $format = null, $interval = 'P1D')
	{
		$step = new \DateInterval($interval);
		$dateMin = Normalizer::getNormalDate($dateMin, $format);
		$dateMax = Normalizer::getNormalDate($dateMax, $format);

		do
		{
			$ret[] = clone $dateMin;
			$dateMin->add($step);
		}
		while ($dateMin <= $dateMax);

		return $ret;
	}

	public static function getRange($rangeType, $date, $format = null)
	{
		$method = 'get' . ucfirst($rangeType) . 'Range';
		if ($rangeType === 'two_weeks')
		{
			$method = 'getTwoWeeksRange';
		}
		if (!method_exists(static::class, $method))
		{
			throw new Main\ArgumentException(sprintf('Unknown range type "%s"', $rangeType), 'rangeType');
		}

		$date = Normalizer::getNormalDate($date, $format);

		return call_user_func_array([static::class, $method], [$date]);
	}

	public static function getWeekRange($date)
	{
		$dateStart = clone $date;
		$dateStart->modify('monday this week');

		$dateEnd = clone $date;
		$dateEnd->modify('sunday this week');

		return [
			$dateStart,
			$dateEnd,
		];
	}

	public static function getMonthRange($date)
	{
		return [
			Normalizer::getNormalDate($date->format('01.m.Y'), 'd.m.Y'),
			Normalizer::getNormalDate($date->format('t.m.Y'), 'd.m.Y'),
		];
	}

	/**
	 * @param \DateTime|Main\Type\Date $date
	 * @param null $quarter
	 * @return \DateTime[]
	 */
	public static function getQuarterRange($date = null, $quarter = null)
	{
		if ($quarter === null)
		{
			$quarter = intval(((int)$date->format('n') + 2) / 3);
		}

		$ranges = [
			1 => ['01.01', '31.03'],
			2 => ['01.04', '30.06'],
			3 => ['01.07', '30.09'],
			4 => ['01.10', '31.12'],
		];

		return [
			Normalizer::getNormalDate($ranges[$quarter][0] . $date->format('Y'), 'd.m.Y'),
			Normalizer::getNormalDate($ranges[$quarter][1] . $date->format('Y'), 'd.m.Y'),
		];
	}

	/**
	 * @param Main\Type\Date $date
	 * @return array
	 */
	public static function getYearRange($date)
	{
		return [
			Normalizer::getNormalDate('01.01.' . $date->format('Y'), 'd.m.Y'),
			Normalizer::getNormalDate('31.12.' . $date->format('Y'), 'd.m.Y'),
		];
	}

	/**
	 * @param Main\Type\Date $date
	 * @return array
	 * @throws \Exception
	 */
	public static function getTwoWeeksRange($date = null)
	{
		if (!$date)
		{
			$date = Main\Type\Date::createFromPhp(new \DateTime('first day of this month'));
		}
		$from = $date;
		$to = clone $date;
		$to->add(new \DateInterval('P14D'));
		return [
			$from,
			$to,
		];
	}
}