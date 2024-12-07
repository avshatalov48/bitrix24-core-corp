<?php

namespace Bitrix\Tasks\Flow;

use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntityCollection;
use Bitrix\Tasks\Flow\Internal\FlowTable;
use Bitrix\Tasks\Internals\Log\Logger;
use Throwable;

class FlowRegistry
{
	public const DEFAULT_SELECT = [
		'*',
	];

	private array $previousSelect = [];

	private static ?self $instance = null;

	private FlowEntityCollection $storage;

	public static function getInstance(): static
	{
		if (self::$instance === null)
		{
			self::$instance = new static();
		}

		return self::$instance;
	}

	private function __construct()
	{
		$this->storage = new FlowEntityCollection();
	}

	final public function get(int $id, array $select = self::DEFAULT_SELECT): ?FlowEntity
	{
		if ($this->isSelectChanged($select))
		{
			$this->invalidateAll();
		}

		if (
			!$this->storage->hasByPrimary($id)
		)
		{
			$this->load([$id], $select);
		}

		if (!$this->storage->hasByPrimary($id))
		{
			return null;
		}

		return $this->storage->getByPrimary($id);
	}

	final public function load(array $ids, array $select = self::DEFAULT_SELECT): self
	{
		if ($this->isSelectChanged($select))
		{
			$this->invalidateAll();
		}

		$this->previousSelect = $select;

		if (empty($ids))
		{
			return $this;
		}

		$ids = $this->getNotStoredIds($ids);
		if (empty($ids))
		{
			return $this;
		}

		if (!in_array('ID', $select, true))
		{
			$select[] = 'ID';
		}

		try
		{
			$autoTasks = FlowTable::query()
				->setSelect($select)
				->whereIn('ID', $ids)
				->fetchCollection();
		}
		catch (Throwable $exception)
		{
			Logger::logThrowable($exception);
			return $this;
		}

		if ($autoTasks->isEmpty())
		{
			return $this;
		}

		foreach ($autoTasks as $autoTask)
		{
			$this->storage->add($autoTask);
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
		$this->storage = new FlowEntityCollection();
		$this->previousSelect = [];
		return $this;
	}

	private function isSelectChanged(array $select): bool
	{
		return !empty(array_diff($this->previousSelect, $select)) || !empty(array_diff($select, $this->previousSelect));
	}

	private function getNotStoredIds(array $ids): array
	{
		return array_diff(array_unique($ids), $this->storage->getIdList());
	}
}