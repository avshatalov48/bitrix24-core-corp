<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Internals\Model\ScorerTable;
use Bitrix\Booking\Internals\Service\CounterDictionary;
use Bitrix\Booking\Internals\Repository\CounterRepositoryInterface;
use Bitrix\Main\Application;

class CounterRepository implements CounterRepositoryInterface
{
	private array $cache;

	public function __construct()
	{
		$this->cache = [];
	}

	public function get(int $userId, CounterDictionary $type = CounterDictionary::Total, int $entityId = 0): int
	{
		return match ($type)
		{
			CounterDictionary::BookingDelayed => $this->getBookingDelayed($userId, $entityId),
			CounterDictionary::BookingUnConfirmed => $this->getBookingUnConfirmed($userId, $entityId),
			CounterDictionary::Total => $this->getTotal($userId),
			default => 0,
		};
	}

	public function getByUser(int $userId): array
	{
		if (isset($this->cache[$userId]))
		{
			return $this->cache[$userId];
		}

		$counters = ScorerTable::query()
			->setSelect(['USER_ID', 'VALUE', 'TYPE', 'ENTITY_ID'])
			->where('USER_ID', '=', $userId)
			->exec()
			->fetchAll()
		;

		$this->cache[$userId] = $counters;
		$this->cache[$userId]['META'] = $this->preComputeCounters($userId);

		return $this->cache[$userId];
	}

	public function up(int $entityId, CounterDictionary $type, int $userId): void
	{
		unset($this->cache[$userId]);

		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();
		$sql = $helper->getInsertIgnore(
			ScorerTable::getTableName(),
			'(ENTITY_ID, TYPE, USER_ID, VALUE)', 'VALUES (' . $entityId . ', \'' . $type->value . '\', ' . $userId . ', 1)'
		);
		$connection->query($sql);
	}

	public function down(int $entityId, CounterDictionary $type, int|null $userId = null): void
	{
		$this->downMultiple([$entityId], [$type], $userId);
	}

	/**
	 * @param int[] $entityIds
	 * @param CounterDictionary[] $types
	 */
	public function downMultiple(array $entityIds, array $types, int|null $userId = null): void
	{
		if (empty($entityIds) && empty($types) && $userId === null)
		{
			return;
		}

		if ($userId !== null)
		{
			unset($this->cache[$userId]);
		}
		else
		{
			// TODO: fix me
			// here we can not determine which users affected by removed counters
			// so for current cache structure, need to clear whole cache
			// alternatively we can make query by filter and get affected users
			$this->cache = [];
		}

		$filter = [];

		if (!empty($entityIds))
		{
			$filter['=ENTITY_ID'] = $entityIds;
		}

		if (!empty($types))
		{
			$filter['=TYPE'] = array_map(static fn (CounterDictionary $type) => $type->value, $types);
		}

		if ($userId)
		{
			$filter['=USER_ID'] = $userId;
		}

		$this->deleteByFilter($filter);
	}

	public function getUsersByCounterType(array $entityIds, array $types): array
	{
		if (empty($entityIds) || empty($types))
		{
			return [];
		}

		return ScorerTable::query()
			->setSelect(['USER_ID'])
			->setDistinct()
			->whereIn('TYPE', array_map(static fn (CounterDictionary $type) => $type->value, $types))
			->whereIn('ENTITY_ID', $entityIds)
			->exec()
			->fetchAll()
		;
	}

	public function getList(int $userId): array
	{
		return [
			'total' => $this->get($userId, CounterDictionary::Total),
			'unConfirmed' => $this->get($userId, CounterDictionary::BookingUnConfirmed),
			'delayed' => $this->get($userId, CounterDictionary::BookingDelayed),
		];
	}

	private function deleteByFilter(array $filter): void
	{
		ScorerTable::deleteByFilter($filter);
	}

	private function getTotal(int $userId): int
	{
		return $this->get($userId, CounterDictionary::BookingUnConfirmed)
			+ $this->get($userId, CounterDictionary::BookingDelayed);
	}

	private function getBookingUnConfirmed(int $userId, int $entityId): int
	{
		$counters = $this->getByUser($userId);

		if (empty($counters))
		{
			return 0;
		}

		if ($entityId === 0)
		{
			return $counters['META']['BOOKING_UNCONFIRMED_TOTAL'];
		}

		return $this->getValueByEntity($counters, $entityId, CounterDictionary::BookingUnConfirmed);
	}

	private function getBookingDelayed(int $userId, int $entityId): int
	{
		$counters = $this->getByUser($userId);

		if (empty($counters))
		{
			return 0;
		}

		if ($entityId === 0)
		{
			return $counters['META']['BOOKING_DELAYED_TOTAL'];
		}

		return $this->getValueByEntity($counters, $entityId, CounterDictionary::BookingDelayed);
	}

	private function preComputeCounters(int $userId): array
	{
		$meta = [
			'TOTAL' => 0,
			'BOOKING_UNCONFIRMED_TOTAL' => 0,
			'BOOKING_DELAYED_TOTAL' => 0,
		];

		if (empty($this->cache[$userId]))
		{
			return $meta;
		}

		foreach ($this->cache[$userId] as $counter)
		{
			if ($counter['TYPE'] === CounterDictionary::BookingUnConfirmed->value)
			{
				$meta['BOOKING_UNCONFIRMED_TOTAL'] += (int)$counter['VALUE'];
			}

			if ($counter['TYPE'] === CounterDictionary::BookingDelayed->value)
			{
				$meta['BOOKING_DELAYED_TOTAL'] += (int)$counter['VALUE'];
			}
		}

		return $meta;
	}

	private function getValueByEntity(array $counters, int $entityId, CounterDictionary $type): int
	{
		foreach ($counters as $key => $counter)
		{
			if ($key === 'META')
			{
				continue;
			}

			if ($counter['TYPE'] == $type->value && $counter['ENTITY_ID'] == $entityId)
			{
				return (int)$counter['VALUE'];
			}
		}

		return 0;
	}
}
