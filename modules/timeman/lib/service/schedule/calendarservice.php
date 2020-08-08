<?php
namespace Bitrix\Timeman\Service\Schedule;

use Bitrix\Timeman\Form\Schedule\CalendarForm;
use Bitrix\Timeman\Helper\Form\Schedule\CalendarFormHelper;
use Bitrix\Timeman\Model\Schedule\Calendar\Calendar;
use Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion;
use Bitrix\Timeman\Repository\Schedule\CalendarRepository;
use Bitrix\Timeman\Service\Schedule\Result\CalendarServiceResult;

class CalendarService
{
	/** @var CalendarRepository */
	private $calendarRepository;

	public function __construct(CalendarRepository $calendarRepository)
	{
		$this->calendarRepository = $calendarRepository;
	}

	public function add(CalendarForm $calendarForm)
	{
		$calendar = Calendar::create(
			$calendarForm->name,
			$calendarForm->parentId,
			$calendarForm->systemCode
		);

		return $this->saveWithExclusions($calendar, $calendarForm);
	}

	public function update($calendarOrId, CalendarForm $calendarForm)
	{
		$calendar = $calendarOrId;
		if (!($calendar instanceof Calendar))
		{
			$calendar = $this->calendarRepository->findByIdWithExclusions($calendarOrId);
		}
		if (!$calendar)
		{
			return (new CalendarServiceResult())->addCalendarNotFoundError();
		}

		$calendar->edit(
			$calendarForm->name,
			$calendarForm->parentId,
			$calendarForm->systemCode
		);
		return $this->saveWithExclusions($calendar, $calendarForm);
	}

	/**
	 * @param Calendar $calendar
	 * @param $calendarForm
	 * @return CalendarServiceResult
	 */
	private function saveWithExclusions($calendar, $calendarForm)
	{
		$parentCalendar = null;
		if ($calendar->getParentCalendarId() > 0)
		{
			$parentCalendar = $this->calendarRepository->findByIdWithExclusions($calendar->getParentCalendarId());
			if (!$parentCalendar || $parentCalendar->getParentCalendarId() > 0)
			{
				$calendar->setParentCalendarId(0);
			}
		}
		$res = $this->calendarRepository->save($calendar);
		if (!$res->isSuccess())
		{
			return CalendarServiceResult::createByResult($res);
		}
		if ($parentCalendar && $calendar->getParentCalendarId() > 0 && !$calendar->obtainParentCalendar())
		{
			$calendar->setParentCalendar($parentCalendar);
		}

		$this->calendarRepository->deleteCalendarExclusions($calendar->getId());
		$calendar->unsetExclusions();

		$dates = CalendarFormHelper::convertDatesToDbFormat($calendarForm->dates);
		foreach ($dates as $year => $yearDatesToSave)
		{
			if ($parentCalendar && $parentCalendar->obtainExclusionsByYear($year))
			{
				$parentYearDates = $parentCalendar->obtainExclusionsByYear($year)->getDates();
				foreach ($parentYearDates as $month => $days)
				{
					if (array_key_exists($month, $yearDatesToSave))
					{
						if ($this->isSameDates($yearDatesToSave[$month], $days))
						{
							unset($yearDatesToSave[$month]);
						}
					}
				}
				$yearDatesToSave = array_filter($yearDatesToSave);
			}
			if (!empty($yearDatesToSave))
			{
				$exclusions = CalendarExclusion::create($calendar->getId(), $year, $yearDatesToSave);

				$exclusionsResult = $this->calendarRepository->save($exclusions);
				if (!$exclusionsResult->isSuccess())
				{
					return CalendarServiceResult::createByResult($exclusionsResult);
				}
				$calendar->addToExclusions($exclusions);
			}
		}
		return (new CalendarServiceResult())->setCalendar($calendar);
	}

	private function isSameDates($parentDatesForMonth, $datesToCompare)
	{
		if (count($parentDatesForMonth) !== count($datesToCompare) ||
			array_diff(array_keys($parentDatesForMonth), array_keys($datesToCompare)) ||
			array_diff(array_keys($datesToCompare), array_keys($parentDatesForMonth)))
		{
			return false;
		}
		return true;
	}

	public function deleteCalendarById($calendarId)
	{
		return $this->calendarRepository->delete($calendarId);
	}
}