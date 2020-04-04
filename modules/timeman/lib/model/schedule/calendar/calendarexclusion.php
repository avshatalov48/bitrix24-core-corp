<?php
namespace Bitrix\Timeman\Model\Schedule\Calendar;

class CalendarExclusion extends EO_CalendarExclusion
{
	public static function create($calendarId, $year, $dates)
	{
		$exc = new static($setDefaultValues = false);
		$exc->setYear($year);
		$exc->setCalendarId($calendarId);
		$exc->setDates($dates);
		return $exc;
	}
}