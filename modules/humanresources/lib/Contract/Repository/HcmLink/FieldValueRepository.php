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

	public function getByUnique(int $entityId, int $fieldId): ?FieldValue;

	/**
	 * @param list<int> $fieldIds
	 * @param list<int> $entityIds
	 * @return FieldValueCollection
	 */
	public function getByFieldIdsAndEntityIds(array $fieldIds, array $entityIds): FieldValueCollection;

	public function listExpiredIds(int $limit = 100): array;

	/**
	 * @param list<int> $ids
	 *
	 * @return void
	 */
	public function removeByIds(array $ids): void;
}