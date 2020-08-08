<?php
namespace Bitrix\Timeman\Repository\Schedule;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;
use Bitrix\Timeman\Model\Schedule\Calendar\Calendar;
use Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusion;
use Bitrix\Timeman\Model\Schedule\Calendar\CalendarExclusionTable;
use Bitrix\Timeman\Model\Schedule\Calendar\CalendarTable;
use Bitrix\Timeman\Model\Schedule\Calendar\EO_Calendar_Collection;

class CalendarRepository
{
	public function findById($vioId)
	{
		return CalendarTable::getById($vioId)->fetchObject();
	}

	public function save($calendar)
	{
		/** @var CalendarExclusion|Calendar $calendar */
		return $calendar->save();
	}

	/**
	 * @param $id
	 * @return Calendar|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findByIdWithExclusions($id)
	{
		return CalendarTable::query()
			->where('ID', $id)
			->addSelect('*')
			->addSelect('EXCLUSIONS')
			->exec()
			->fetchObject();
	}

	/**
	 * @param $systemCodes
	 * @return EO_Calendar_Collection
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findByCodesWithExclusions($systemCodes)
	{
		if (empty($systemCodes))
		{
			return [];
		}
		return CalendarTable::query()
			->whereIn('SYSTEM_CODE', $systemCodes)
			->addSelect('*')
			->addSelect('EXCLUSIONS')
			->exec()
			->fetchCollection();
	}

	public function deleteCalendarExclusions($calendarId, $year = null)
	{
		if (!is_null($year))
		{
			return CalendarExclusionTable::delete([
				'CALENDAR_ID' => $calendarId,
				'YEAR' => $year,
			]);
		}
		$calendarId = (int)$calendarId;
		Application::getConnection()->query('DELETE FROM ' . CalendarExclusionTable::getTableName() . " WHERE CALENDAR_ID = $calendarId");
		return new Result();
	}

	public function delete($id)
	{
		$id = (int)$id;
		$res = CalendarTable::delete($id);
		if ($res->isSuccess())
		{
			$this->deleteCalendarExclusions($id);
		}
		return $res;
	}

	public function findAllBy($fieldsToSelect, $filter = null)
	{
		$resultQuery = CalendarTable::query();
		foreach ($fieldsToSelect as $fieldToSelect)
		{
			$resultQuery->addSelect($fieldToSelect);
		}
		if ($filter)
		{
			$resultQuery->where($filter);
		}
		return $resultQuery->exec()
			->fetchCollection();
	}

	/**
	 * @param $calendarId
	 * @param null $year
	 * @return Calendar|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findByIdWithParentCalendarExclusions($calendarId, $year = null)
	{
		$res = CalendarTable::query()
			->addSelect('*')
			->addSelect('EXCLUSIONS')
			->addSelect('PARENT_CALENDAR')
			->addSelect('PARENT_CALENDAR.EXCLUSIONS')
			->where('ID', $calendarId);
		if ($year !== null)
		{
			$res->where(Query::filter()
				->logic('or')
				->where('EXCLUSIONS.YEAR', $year)
				->where('PARENT_CALENDAR.EXCLUSIONS.YEAR', $year)
			);
		}
		return $res
			->exec()
			->fetchObject();
	}
}