<?php


namespace Bitrix\Crm\Service\Broker;


use Bitrix\Crm\Service\Broker;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;

class Dynamic extends Broker
{
	protected $entityTypeId;

	protected function loadEntry(int $id)
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

	protected function loadEntries(array $ids): array
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

				/*$entries[$entityTypeId][$id] = [
					'TITLE' => $dynamicEntity->getHeading(),
					'ID' => $id,
				];*/
			}
		}

		return $entries;
	}

	/**
	 * @return Factory|null
	 * @throws Exception
	 */
	protected function getFactory(): ?Factory
	{
		$entityTypeId = $this->getEntityTypeId();
		if (!$entityTypeId)
		{
			throw new Exception('Must set EntityTypeId before');
		}

		return Container::getInstance()->getFactory($entityTypeId);
	}

	/**
	 * @return string
	 */
	protected function getEntityTypeName(): string
	{
		return  \CCrmOwnerType::ResolveName($this->getEntityTypeId());
	}

	/**
	 * @return int|null
	 */
	protected function getEntityTypeId(): ?int
	{
		return $this->entityTypeId;
	}

	/**
	 * @param int $entityTypeId
	 * @return $this
	 */
	public function setEntityTypeId(int $entityTypeId): Dynamic
	{
		$this->entityTypeId = $entityTypeId;
		return $this;
	}

	protected function getFromCache(int $id)
	{
		$this->prepareCache();
		$entityTypeName = $this->getEntityTypeName();
		return $this->cache[$entityTypeName][$id] ?? null;
	}

	protected function addToCache(int $id, $entry): void
	{
		$this->prepareCache();
		$entityTypeName = $this->getEntityTypeName();
		$this->cache[$entityTypeName][$id] = $entry;
	}

	protected function addBunchToCache(array $entries): void
	{
		$this->prepareCache();
		$entityTypeName = $this->getEntityTypeName();
		$this->cache[$entityTypeName] = $entries + $this->cache[$entityTypeName];
	}

	protected function prepareCache(): void
	{
		$entityTypeName = $this->getEntityTypeName();
		if (!is_array($this->cache[$entityTypeName]))
		{
			$this->cache[$entityTypeName] = [];
		}
	}
}
