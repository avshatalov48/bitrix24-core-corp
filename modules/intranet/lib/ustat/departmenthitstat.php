<?php

namespace Bitrix\Intranet\UStat;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlException;
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
		$fields = [
			'TOTAL' => new SqlExpression('?#.?# + 1', DepartmentDayTable::getTableName(), 'TOTAL')
		];
		$values = [];
		if (DepartmentDayTable::getEntity()->hasField($section))
		{
			$fields[$section] = new SqlExpression('?#.?# + 1', DepartmentDayTable::getTableName(), $section);
		}

		foreach ($this->departmentIds as $id)
		{
			$value = [
				'DEPT_ID' => $id,
				'DAY' => $day,
				'TOTAL' => 1,
			];
			if (DepartmentDayTable::getEntity()->hasField($section))
			{
				$value[$section] = 1;
			}
			$values[] = $value;
		}

		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();
		$query = $helper->prepareMergeValues(
			DepartmentDayTable::getTableName(),
			[
				'DEPT_ID',
				'DAY',
			],
			$values,
			$fields
		);
		$connection->queryExecute($query);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function hour(string $section, DateTime $hour): void
	{
		$fields = [
			'TOTAL' => new SqlExpression('?#.?# + 1', DepartmentHourTable::getTableName(),'TOTAL')
		];
		$values = [];
		if (DepartmentHourTable::getEntity()->hasField($section))
		{
			$fields[$section] = new SqlExpression('?#.?# + 1', DepartmentHourTable::getTableName(), $section);
		}

		foreach ($this->departmentIds as $id)
		{
			$value = [
				'DEPT_ID' => $id,
				'HOUR' => $hour,
				'TOTAL' => 1,
			];
			if (DepartmentHourTable::getEntity()->hasField($section))
			{
				$value[$section] = 1;
			}
			$values[] = $value;
		}

		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();
		$query = $helper->prepareMergeValues(
			DepartmentHourTable::getTableName(),
			[
				'DEPT_ID',
				'HOUR',
			],
			$values,
			$fields
		);

		$connection->queryExecute($query);
	}
}