<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Internals\CounterDictionary;
use Bitrix\Booking\Internals\Model\ScorerTable;
use Bitrix\Booking\Internals\Repository\CounterRepositoryInterface;
use Bitrix\Main\Application;

class CounterRepository implements CounterRepositoryInterface
{
	public function up(int $entityId, CounterDictionary $type, int $userId): void
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();
		$sql = $helper->getInsertIgnore(
			ScorerTable::getTableName(),
			'(ENTITY_ID, TYPE, USER_ID, VALUE)', 'VALUES (' . $entityId . ', \'' . $type->value . '\', ' . $userId . ', 1)'
		);
		$connection->query($sql);
	}

	public function down(int $entityId, CounterDictionary $type, ?int $userId = null): void
	{
		$filter = [
			'=ENTITY_ID' => $entityId,
			'=TYPE' => $type->value,
		];

		if ($userId)
		{
			$filter['=USER_ID'] = $userId;
		}

		ScorerTable::deleteByFilter($filter);
	}

	public function getByUser(int $userId): array
	{
		$result = ScorerTable::query()
			->setSelect(['USER_ID', 'VALUE', 'TYPE', 'ENTITY_ID'])
			->where('USER_ID', '=', $userId)
			->exec()
			->fetchAll()
		;

		return $result ?? [];
	}

	public function getUsersByCounterType(int $entityId, CounterDictionary $type): array
	{
		return ScorerTable::query()
			->setSelect(['USER_ID'])
			->where('TYPE', '=', $type->value)
			->where('ENTITY_ID', '=', $entityId)
			->exec()
			->fetchAll()
		;
	}

	public function getList(int $userId): array
	{
		return [];
	}

	public function get(int $userId, CounterDictionary $type = CounterDictionary::Total, int $entityId = 0): int
	{
		return 0;
	}
}
