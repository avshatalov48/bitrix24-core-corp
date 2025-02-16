<?php

namespace Bitrix\Crm\Service\Broker;

use Bitrix\Crm\Integration\Rest\EventManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Broker;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Event;
use CCrmOwnerType;

/**
 * @method Item|null getById(int $id)
 * @method Item[] getBunchByIds(array $ids)
 */
class Dynamic extends Broker
{
	protected ?string $eventEntityAdd = EventManager::EVENT_DYNAMIC_ITEM_ADD;
	protected ?string $eventEntityUpdate = EventManager::EVENT_DYNAMIC_ITEM_UPDATE;
	protected ?string $eventEntityDelete = EventManager::EVENT_DYNAMIC_ITEM_DELETE;

	protected ?int $entityTypeId;

	final public function setEntityTypeId(int $entityTypeId): Dynamic
	{
		$this->entityTypeId = $entityTypeId;

		return $this;
	}

	/**
	 * @override
	 */
	final public function resetAllCache(): void
	{
		$entityTypeName = $this->getEntityTypeName();
		$this->cache[$entityTypeName] = [];
	}

	// region EVENT HANDLERS
	/**
	 * @override
	 */
	final public function setCache(mixed $fields): void
	{
		[$id, $item] = $this->fetchEventData($fields);
		if ($id > 0 && $item !== null)
		{
			$this->addToCache($id, $item);
		}
	}

	/**
	 * @override
	 */
	final public function updateCache(mixed $fields): void
	{
		[$id, $item] = $this->fetchEventData($fields);
		if ($id > 0 && $item !== null)
		{
			$this->addToCache($id, $item);
		}
	}

	/**
	 * @override
	 */
	final public function deleteCache($value): void
	{
		[$value, $item] = $this->fetchEventData($value);
		if ($value > 0)
		{
			$this->removeFromCache($value);
		}
	}
	// endregion

	/**
	 * @override
	 */
	final protected function loadEntry(int $id)
	{
		$factory = $this->getFactory();
		if ($factory)
		{
			return $factory->getItems([
				'filter' => [
					'@ID' => $id,
				],
			])[0] ?? null;
		}

		return null;
	}

	/**
	 * @override
	 */
	final protected function loadEntries(array $ids): array
	{
		$entries = [];

		$factory = $this->getFactory();
		if ($factory)
		{
			$dynamicEntityList = $factory->getItems([
				'filter' => [
					'@ID' => $ids,
				],
			]);

			foreach ($dynamicEntityList as $dynamicEntity)
			{
				$id = $dynamicEntity->getId();
				$entries[$id] = $dynamicEntity;
			}
		}

		return $entries;
	}

	/**
	 * @override
	 */
	final protected function getFromCache(int $id)
	{
		$this->prepareCache();
		$entityTypeName = $this->getEntityTypeName();

		return $this->cache[$entityTypeName][$id] ?? null;
	}

	/**
	 * @override
	 */
	final protected function addToCache(int $id, $entry): void
	{
		$this->prepareCache();
		$entityTypeName = $this->getEntityTypeName();
		$this->cache[$entityTypeName][$id] = $entry;
	}

	/**
	 * @override
	 */
	final protected function addBunchToCache(array $entries): void
	{
		$this->prepareCache();
		$entityTypeName = $this->getEntityTypeName();
		$this->cache[$entityTypeName] = $entries + $this->cache[$entityTypeName];
	}

	/**
	 * @override
	 */
	final protected function removeFromCache(int $id): void
	{
		if ($id > 0)
		{
			$this->prepareCache();
			$entityTypeName = $this->getEntityTypeName();
			unset($this->cache[$entityTypeName][$id]);
		}
	}

	protected function getFactory(): ?Factory
	{
		$entityTypeId = $this->getEntityTypeId();
		if (!$entityTypeId)
		{
			throw new Exception('Must set EntityTypeId before');
		}

		return Container::getInstance()->getFactory($entityTypeId);
	}

	protected function getEntityTypeName(): string
	{
		return CCrmOwnerType::ResolveName($this->getEntityTypeId());
	}

	protected function getEntityTypeId(): ?int
	{
		return $this->entityTypeId;
	}

	protected function prepareCache(): void
	{
		$entityTypeName = $this->getEntityTypeName();
		if (!is_array($this->cache[$entityTypeName] ?? null))
		{
			$this->cache[$entityTypeName] = [];
		}
	}

	private function fetchEventData(mixed $value): array
	{
		if ($value instanceof Event)
		{
			return [
				(int)($value->getParameters()['id'] ?? 0),
				$value->getParameters()['item'] ?? null,
			];
		}

		return [];
	}
}
