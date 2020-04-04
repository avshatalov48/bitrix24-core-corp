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


		$calendarId = $calendar->getId();
		$this->calendarRepository->deleteCalendarExclusions($calendarId);

		$dates = CalendarFormHelper::convertDatesToDbFormat($calendarForm->dates);
		foreach ($dates as $year => $yearDatesToSave)
		{
			$exclusions = CalendarExclusion::create($calendarId, $year, $yearDatesToSave);
			if ($parentCalendar && $parentCalendar->obtainExclusionsByYear($year))
			{
				$parentYearDates = $parentCalendar->obtainExclusionsByYear($year)->getDates();
				if ($this->isSameDates($parentYearDates, $yearDatesToSave))
				{
					continue;
				}
			}
			$exclusionsResult = $this->calendarRepository->save($exclusions);
			if (!$exclusionsResult->isSuccess())
			{
				return CalendarServiceResult::createByResult($exclusionsResult);
			}
			$calendar->addToExclusions($exclusions);
		}
		return (new CalendarServiceResult())->setCalendar($calendar);
	}

	private function isSameDates($parentDatesForYear, $datesToCompare)
	{
		foreach ($parentDatesForYear as $month => $parentMonthDates)
		{
			if (!array_key_exists($month, $datesToCompare))
			{
				if (empty($parentMonthDates))
				{
					continue;
				}
				return false;
			}
			$datesSource = array_map('intval', array_keys($parentMonthDates));

			$datesToCompareForYear = array_map('intval', array_keys($datesToCompare[$month]));

			if (array_diff($datesToCompareForYear, $datesSource)
				|| array_diff($datesSource, $datesToCompareForYear))
			{
				return false;
			}
		}

		return true;
	}

	public function deleteCalendarById($calendarId)
	{
		return $this->calendarRepository->delete($calendarId);
	}
}