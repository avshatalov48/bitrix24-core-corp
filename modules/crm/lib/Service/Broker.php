<?php

namespace Bitrix\Crm\Service;

use Bitrix\Main\EventManager;
use Bitrix\Main\Type\Collection;

abstract class Broker
{
	private const MODULE = 'crm';

	protected array $cache = [];

	protected ?string $eventEntityAdd = null;
	protected ?string $eventEntityUpdate = null;
	protected ?string $eventEntityDelete = null;

	/**
	 * Load single entry from the DB
	 *
	 * @param int $id
	 *
	 * @return mixed|null
	 */
	abstract protected function loadEntry(int $id);

	/**
	 * Load multiple entries from the DB.
	 *
	 * @param array $ids
	 *
	 * @return array Key - entry ID, value - entry
	 */
	abstract protected function loadEntries(array $ids): array;

	public function __construct()
	{
		$this->initCacheManagementEventHandlers();
	}

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

	public function resetAllCache(): void
	{
		$this->cache = [];
	}

	// region EVENT HANDLERS
	public function setCache(mixed $fields): void
	{
		$entityId = $this->extractEntityId($fields);
		if ($entityId > 0)
		{
			$this->removeFromCache($entityId);
			$this->addToCache($entityId, $this->getById($entityId));
		}
	}

	public function updateCache(mixed $fields): void
	{
		$entityId = $this->extractEntityId($fields);
		if ($entityId > 0)
		{
			$this->removeFromCache($entityId);
			$this->addToCache($entityId, $this->getById($entityId));
		}
	}

	public function deleteCache(mixed $value): void
	{
		$entityId = $this->extractEntityId($value);
		if ($entityId > 0)
		{
			$this->removeFromCache($entityId);
		}
	}
	// endregion

	protected function getFromCache(int $id)
	{
		return $this->cache[$id] ?? null;
	}

	protected function addToCache(int $id, mixed $entry): void
	{
		$this->cache[$id] = $entry;
	}

	protected function removeFromCache(int $id): void
	{
		if ($id > 0)
		{
			unset($this->cache[$id]);
		}
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

	final protected function initCacheManagementEventHandlers(): void
	{
		$eventManager = EventManager::getInstance();
		if ($this->eventEntityAdd)
		{
			$eventManager->addEventHandler(
				self::MODULE,
				$this->eventEntityAdd,
				[$this, 'setCache']
			);
		}

		if ($this->eventEntityUpdate)
		{
			$eventManager->addEventHandler(
				self::MODULE,
				$this->eventEntityUpdate,
				[$this, 'updateCache']
			);
		}

		if ($this->eventEntityDelete)
		{
			$eventManager->addEventHandler(
				self::MODULE,
				$this->eventEntityDelete,
				[$this, 'deleteCache']
			);
		}

		$this->initAdditionalCacheManagementEventHandlers($eventManager);
	}

	protected function initAdditionalCacheManagementEventHandlers(EventManager $eventManager): void {}

	private function extractEntityId(mixed $value): int
	{
		if (
			is_array($value)
			&& isset($value['ID'])
			&& is_numeric($value['ID'])
		)
		{
			return (int)$value['ID'];
		}

		if (is_numeric($value))
		{
			return (int)$value;
		}

		return 0;
	}
}
