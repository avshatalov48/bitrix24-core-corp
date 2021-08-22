<?php

namespace Bitrix\Crm\Service;

use Bitrix\Main\Type\Collection;

abstract class Broker
{
	protected $cache = [];

	public function getById(int $id)
	{
		return $this->getFromCache($id) ?? $this->getEntry($id);
	}

	public function getBunchByIds(array $ids): array
	{
		Collection::normalizeArrayValuesByInt($ids);
		if (empty($ids))
		{
			return [];
		}

		$cachedEntries = [];
		$idsNotCached = [];
		foreach ($ids as $id)
		{
			$cachedEntry = $this->getFromCache($id);
			if ($cachedEntry)
			{
				$cachedEntries[$id] = $cachedEntry;
			}
			else
			{
				$idsNotCached[] = $id;
			}
		}

		return ($this->getEntries($idsNotCached) + $cachedEntries);
	}

	protected function getFromCache(int $id)
	{
		return $this->cache[$id] ?? null;
	}

	protected function addToCache(int $id, $entry): void
	{
		$this->cache[$id] = $entry;
	}

	protected function addBunchToCache(array $entries): void
	{
		$this->cache = $entries + $this->cache;
	}

	protected function getEntry(int $id)
	{
		$entry = $this->loadEntry($id);
		if (!$entry)
		{
			return null;
		}

		$this->addToCache($id, $entry);
		return $entry;
	}

	/**
	 * Load single entry from the DB
	 *
	 * @param int $id
	 *
	 * @return mixed|null
	 */
	abstract protected function loadEntry(int $id);

	protected function getEntries(array $ids): array
	{
		if (empty($ids))
		{
			return [];
		}

		$entries = $this->loadEntries($ids);
		$this->addBunchToCache($entries);

		return $entries;
	}

	/**
	 * Load multiple entries from the DB.
	 *
	 * @param array $ids
	 *
	 * @return array Key - entry ID, value - entry
	 */
	abstract protected function loadEntries(array $ids): array;
}