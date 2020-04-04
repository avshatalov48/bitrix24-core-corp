<?php
namespace Bitrix\Timeman\Helper\Form\Schedule;

class CalendarFormHelper
{

	public static function convertDatesToDbFormat($dates)
	{
		$res = [];
		foreach ((array)$dates as $year => $months)
		{
			$res[$year] = [];
			if (!is_array($months) || empty($months))
			{
				continue;
			}
			foreach ($months as $month => $days)
			{
				if (!is_array($days) || empty($days))
				{
					continue;
				}
				$res[$year][$month + 1] = [];
				foreach ($days as $day => $time)
				{
					$res[$year][$month + 1][$day] = $time;
				}
			}
		}
		return $res;
	}

	public static function convertDatesToViewFormat($dates)
	{
		if (!$dates)
		{
			return $dates;
		}
		$res = [];
		foreach ($dates as $year => $months)
		{
			$res[$year] = [];
			foreach ($months as $month => $days)
			{
				foreach ($days as $day => $time)
				{
					$res[$year][$month - 1][$day] = $time;
				}
			}
		}
		return $res;
	}
}