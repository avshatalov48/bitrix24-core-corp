<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository;

use Bitrix\Booking\Internals\Service\CounterDictionary;

interface CounterRepositoryInterface
{
	public function getByUser(int $userId): array;
	public function get(int $userId, CounterDictionary $type = CounterDictionary::Total, int $entityId = 0): int;
	public function up(int $entityId, CounterDictionary $type, int $userId): void;
	public function down(int $entityId, CounterDictionary $type, int|null $userId = null): void;

	/**
	 * @param int[] $entityIds
	 * @param CounterDictionary[] $types
	 */
	public function downMultiple(array $entityIds, array $types, int|null $userId = null): void;

	/**
	 * @param int[] $entityIds
	 * @param CounterDictionary[] $types
	 * @return array
	 */
	public function getUsersByCounterType(array $entityIds, array $types): array;
	public function getList(int $userId): array;
}
