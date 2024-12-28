<?php

namespace Bitrix\HumanResources\Contract\Repository\HcmLink;

use Bitrix\HumanResources\Item\Collection\HcmLink\FieldValueCollection;
use Bitrix\HumanResources\Item\HcmLink\Employee;
use Bitrix\HumanResources\Item\HcmLink\Field;
use Bitrix\HumanResources\Item\HcmLink\FieldValue;

interface FieldValueRepository
{
	public function add(FieldValue $item): FieldValue;

	public function update(FieldValue $item): FieldValue;

	public function getByUnique(int $employeeId, int $fieldId): ?FieldValue;

	public function getByFieldAndEmployee(Field $field, Employee $employee): ?FieldValue;

	public function getByFieldIdsAndEmployeeIds(array $fieldIds, array $employeeIds): FieldValueCollection;

	public function listExpiredIds(int $limit = 100): array;

	/**
	 * @param list<int> $ids
	 *
	 * @return void
	 */
	public function removeByIds(array $ids): void;
}