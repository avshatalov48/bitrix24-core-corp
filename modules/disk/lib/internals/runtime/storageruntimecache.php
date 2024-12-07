<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internals\Runtime;

use Bitrix\Disk\Storage;

final class StorageRuntimeCache
{
	private array $cache = [];
	private array $indexByEntityType = [];

	public function __construct()
	{
	}

	public function store(Storage $storage): void
	{
		$id = (int)$storage->getId();

		$this->cache[$id] = $storage;
		$this->indexByEntityType[$this->getEntityTypeIndex($storage)] = $id;
	}

	public function remove(Storage $storage): void
	{
	    $id = (int)$storage->getId();
		$entityTypeIndex = $this->getEntityTypeIndex($storage);

		unset($this->cache[$id], $this->indexByEntityType[$entityTypeIndex]);
	}

	public function getById(int $storageId): ?Storage
	{
		return $this->cache[$storageId] ?? null;
	}

	public function getByEntityType(array $entityType): ?Storage
	{
		$index = $this->buildEntityTypeIndex($entityType);

		return $this->cache[$this->indexByEntityType[$index]] ?? null;
	}

	public function isLoadedById(int $storageId): bool
	{
		return isset($this->cache[$storageId]);
	}

	public function isLoadedByEntityType(array $entityType): bool
	{
		$index = $this->buildEntityTypeIndex($entityType);

		return isset($this->indexByEntityType[$index]);
	}

	private function buildEntityTypeIndex(array $entityType): string
	{
		return implode('|', [
			'MODULE_ID' => $entityType['MODULE_ID'],
			'ENTITY_TYPE' => $entityType['ENTITY_TYPE'],
			'ENTITY_ID' => $entityType['ENTITY_ID'],
		]);
	}

	private function getEntityTypeIndex(Storage $storage): string
	{
		return $this->buildEntityTypeIndex([
			'MODULE_ID' => $storage->getModuleId(),
			'ENTITY_TYPE' => $storage->getEntityType(),
			'ENTITY_ID' => $storage->getEntityId(),
		]);
	}
}