<?php

namespace Bitrix\StaffTrack\Shift;

use Bitrix\StaffTrack\Model\Shift;
use Bitrix\StaffTrack\Model\ShiftCollection;
use Bitrix\StaffTrack\Model\ShiftTable;
use Bitrix\StaffTrack\Trait\Singleton;

class ShiftRegistry
{
	use Singleton;

	public const DEFAULT_SELECT = ['*', 'GEO.*', 'CANCELLATION.*'];
	private array $previousSelect = [];
	private ShiftCollection $storage;

	private function __construct()
	{
		$this->storage = new ShiftCollection();
	}

	/**
	 * @param int $id
	 * @param array $select
	 * @return Shift|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	final public function get(int $id, array $select = self::DEFAULT_SELECT): ?Shift
	{
		if ($this->isSelectChanged($select))
		{
			$this->invalidate($id);
		}

		if (!$this->storage->hasByPrimary($id))
		{
			$this->load([$id], $select);
		}

		if (!$this->storage->hasByPrimary($id))
		{
			return null;
		}

		return $this->storage->getByPrimary($id);
	}

	/**
	 * @param array $ids
	 * @param array $select
	 * @return $this
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	final public function load(array $ids, array $select): self
	{
		if ($this->isSelectChanged($select))
		{
			$this->invalidate(...$ids);
		}

		if (empty($ids))
		{
			return $this;
		}

		$ids = $this->getNotStoredIds($ids);
		if (empty($ids))
		{
			return $this;
		}

		$this->previousSelect = $select;

		if (!in_array('ID', $select, true))
		{
			$select[] = 'ID';
		}

			$shiftCollection = ShiftTable::query()
				->setSelect($select)
				->whereIn('ID', $ids)
				->exec()
				->fetchCollection()
			;

		if ($shiftCollection->isEmpty())
		{
			return $this;
		}

		foreach ($shiftCollection as $shift)
		{
			$this->storage->add($shift);
		}

		return $this;
	}

	public function invalidate(int ...$ids): static
	{
		foreach ($ids as $id)
		{
			$this->storage->removeByPrimary($id);
		}

		return $this;
	}

	public function invalidateAll(): static
	{
		$this->storage = new ShiftCollection();
		$this->previousSelect = [];
		return $this;
	}

	private function isSelectChanged(array $select): bool
	{
		return !empty(array_diff($this->previousSelect, $select));
	}

	private function getNotStoredIds(array $ids): array
	{
		return array_diff(array_unique($ids), $this->storage->getIdList());
	}
}