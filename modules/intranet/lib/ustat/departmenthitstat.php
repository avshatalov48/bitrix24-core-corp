<?php

namespace Bitrix\Intranet\UStat;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;

class DepartmentHitStat
{
	private array $departmentIds;

	public function __construct(array $departmentIds)
	{
		$this->setDepartmentIds($departmentIds);
	}

	private function setDepartmentIds(array $departmentIds): void
	{
		$this->departmentIds = array_filter($departmentIds, function ($id) {
			return (int)$id > 0;
		});
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 *
	 * @return EO_DepartmentDay_Collection
	 */
	private function getDepartmentsHasDayStatistic(DateTime $date)
	{
		return DepartmentDayTable::query()
			->whereIn('DEPT_ID', $this->departmentIds)
			->where('DAY', '=', $date)
			->fetchCollection();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 *
	 * @return EO_DepartmentHour_Collection
	 */
	private function getDepartmentsHasHourStatistic(DateTime $date)
	{
		return DepartmentHourTable::query()
			->whereIn('DEPT_ID', $this->departmentIds)
			->where('HOUR', '=', $date)
			->fetchCollection();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function day(string $section, DateTime $day): void
	{
		$collection = $this->getDepartmentsHasDayStatistic($day);
		$departmentIdsWithoutStat = $this->departmentIds;

		foreach ($collection as $item)
		{
			$departmentIdsWithoutStat = array_filter($departmentIdsWithoutStat, function ($id) use($item) {
				return $item->getDeptId() !== (int)$id;
			});

			if (DepartmentDayTable::getEntity()->hasField($section))
			{
				$item->set($section, new SqlExpression('?# + 1', $section));
			}

			$item->setTotal(new SqlExpression('?# + 1', 'TOTAL'));
		}
		foreach ($departmentIdsWithoutStat as $id)
		{
			$departmentDayTable = DepartmentDayTable::createObject();
			if (DepartmentDayTable::getEntity()->hasField($section))
			{
				$departmentDayTable->set($section, 1);
			}
			$departmentDayTable->setDay($day);
			$departmentDayTable->setDeptId($id);
			$departmentDayTable->setTotal(1);
			$collection[] = $departmentDayTable;
		}
		$collection->save();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function hour(string $section, DateTime $hour): void
	{
		$collection = $this->getDepartmentsHasHourStatistic($hour);
		$departmentIdsWithoutStat = $this->departmentIds;

		foreach ($collection as $item)
		{
			$departmentIdsWithoutStat = array_filter($departmentIdsWithoutStat, function ($id) use($item) {
				return $item->getDeptId() !== (int)$id;
			});

			if (DepartmentHourTable::getEntity()->hasField($section))
			{
				$item->set($section, new SqlExpression('?# + 1', $section));
			}

			$item->setTotal(new SqlExpression('?# + 1', 'TOTAL'));
		}
		foreach ($departmentIdsWithoutStat as $id)
		{
			$departmentDayTable = DepartmentHourTable::createObject();
			if (DepartmentHourTable::getEntity()->hasField($section))
			{
				$departmentDayTable->set($section, 1);
			}
			$departmentDayTable->setHour($hour);
			$departmentDayTable->setDeptId($id);
			$departmentDayTable->setTotal(1);
			$collection[] = $departmentDayTable;
		}
		$collection->save();
	}
}