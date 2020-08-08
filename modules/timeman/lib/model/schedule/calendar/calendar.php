<?php
namespace Bitrix\Timeman\Model\Schedule\Calendar;

class Calendar extends EO_Calendar
{
	public static function create($name, $parentCalendarId = 0, $systemCode = null)
	{
		$calendar = new static();
		$calendar->setName($name);
		$calendar->setParentCalendarId($parentCalendarId);

		$calendar->setSystemCode(CalendarTable::SYSTEM_CODE_NONE);
		if (in_array($systemCode, CalendarTable::getAllSystemCodes(), true))
		{
			$calendar->setSystemCode($systemCode);
		}

		return $calendar;
	}

	public function edit($name, $parentCalendarId = 0, $systemCode = null)
	{
		$this->setName($name);
		$this->setParentCalendarId($parentCalendarId);
		if (in_array($systemCode, CalendarTable::getAllSystemCodes(), true))
		{
			$this->setSystemCode($systemCode);
		}
	}

	/**
	 * @param $calendarId
	 * @param $year
	 * @return CalendarExclusion|null
	 */
	public function obtainExclusionsByYear($year)
	{
		if ($this->obtainExclusions() && $this->obtainExclusions() instanceof EO_CalendarExclusion_Collection)
		{
			return $this->obtainExclusions()->getByPrimary(['CALENDAR_ID' => $this->getId(), 'YEAR' => $year]);
		}
		return null;
	}

	public function obtainFinalExclusions()
	{
		$result = [];
		foreach ($this->obtainExclusions() as $mineExclusion)
		{
			$result[$mineExclusion->getYear()] = $mineExclusion->getDates();
		}

		if ($this->obtainParentCalendar())
		{
			$parentExclusions = $this->obtainParentCalendar()->obtainExclusions();
			foreach ($parentExclusions as $parentExclusion)
			{
				foreach ($parentExclusion->getDates() as $month => $days)
				{
					if (!array_key_exists($parentExclusion->getYear(), $result))
					{
						$result[$parentExclusion->getYear()] = $parentExclusion->getDates();
					}
					elseif (!array_key_exists($month, $result[$parentExclusion->getYear()]))
					{
						$result[$parentExclusion->getYear()][$month] = $days;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @return Calendar|null
	 */
	public function obtainParentCalendar()
	{
		if ($this->entity->hasField('PARENT_CALENDAR'))
		{
			return $this->get('PARENT_CALENDAR');
		}
		return null;
	}

	/**
	 * @return EO_CalendarExclusion_Collection|array
	 */
	public function obtainExclusions()
	{
		try
		{
			$exclusions = $this->get('EXCLUSIONS');
			if ($exclusions === null)
			{
				return [];
			}
			return $exclusions;
		}
		catch (\Exception $exc)
		{
			return [];
		}
	}

	public function hasHoliday(\DateTime $date)
	{
		$exclusionDates = $this->obtainFinalExclusions();
		$year = $date->format('Y');
		$month = $date->format('n');
		$day = $date->format('j');
		return array_key_exists($year, $exclusionDates) &&
			   array_key_exists($month, $exclusionDates[$year]) &&
			   array_key_exists($day, $exclusionDates[$year][$month]);
	}
}