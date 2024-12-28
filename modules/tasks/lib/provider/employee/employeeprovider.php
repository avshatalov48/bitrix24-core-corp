<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Provider\Employee;

use Bitrix\Main\Type\Collection;
use Bitrix\Main\UserTable;

class EmployeeProvider
{
	protected static ?self $instance = null;

	public static function getInstance(): static
	{
		if (static::$instance === null)
		{
			static::$instance = new static();
		}

		return static::$instance;
	}

	public function splitIntoEmployeesAndGuests(array $userIds): array
	{
		Collection::normalizeArrayValuesByInt($userIds, false);

		if (empty($userIds))
		{
			return [
				0 => [],
				1 => [],
			];
		}

		$employees = UserTable::query()
			->addSelect('ID')
			->addFilter('!UF_DEPARTMENT', false)
			->setCacheTtl(10)
			->whereIn('ID', $userIds)
			->exec()
			->fetchAll()
		;

		$employeeIds = array_column($employees, 'ID');

		Collection::normalizeArrayValuesByInt($employeeIds, false);

		$guestIds = array_diff($userIds, $employeeIds);

		return [
			0 => $employeeIds,
			1 => $guestIds,
		];
	}
}
