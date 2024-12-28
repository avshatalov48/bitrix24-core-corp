<?php

namespace Bitrix\HumanResources\Contract\Repository\HcmLink;

use Bitrix\HumanResources\Item\Collection\HcmLink\EmployeeCollection;
use Bitrix\HumanResources\Item\HcmLink\Employee;
use Bitrix\Main\Result;

interface EmployeeRepository
{
	public function save(Employee $item): Employee;

	public function add(Employee $employee): Employee;

	public function update(Employee $employee): Employee;

	public function getByUnique(int $companyId, string $code): ?Employee;

	public function getCollectionByPersonIds(array $personIds, ?int $limit): EmployeeCollection;

	public function getSeveralByPersonIds(array $personIds): EmployeeCollection;

	public function hasSeveralByPersonIds(array $personIds): bool;

	public function getByPersonId(int $personId): EmployeeCollection;

	/**
	 * @param list<int> $personIds
	 *
	 * @return EmployeeCollection
	 */
	public function getByPersonIds(array $personIds): EmployeeCollection;

	public function getByIds(array $ids): EmployeeCollection;

	public function deleteById(int $id): Result;

	public function listMappedUserIdWithOneEmployeePosition(int $companyId, int ...$userIds): array;

}