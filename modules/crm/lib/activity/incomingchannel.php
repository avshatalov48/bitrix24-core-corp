<?php

namespace Bitrix\Crm\Activity;

use Bitrix\Crm\Activity\Entity\IncomingChannelTable;

class IncomingChannel
{
	private array $cache = [];

	public static function getInstance(): self
	{
		return new self();
	}

	protected function __construct()
	{
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventNamePrefix = IncomingChannelTable::getEntity()->getNamespace() . IncomingChannelTable::getEntity()->getName();

		$eventManager->addEventHandler('crm', $eventNamePrefix . '::onAfterAdd', [$this, 'clearCache']);
		$eventManager->addEventHandler('crm', $eventNamePrefix . '::onAfterUpdate', [$this, 'clearCache']);
		$eventManager->addEventHandler('crm', $eventNamePrefix . '::onAfterDelete', [$this, 'clearCache']);
	}

	public function isIncomingChannel(int $activityId): bool
	{
		return (bool)$this->getIdByActivityId($activityId);
	}

	/**
	 * @param int[] $activityIds
	 * @return int[]
	 */
	public function getIncomingChannelActivityIds(array $activityIds): array
	{
		$activityIds = array_filter($activityIds);
		if (empty($activityIds))
		{
			return [];
		}

		return IncomingChannelTable::query()
			->whereIn('ACTIVITY_ID', $activityIds)
			->setSelect(['ACTIVITY_ID'])
			->fetchCollection()
			->getActivityIdList()
		;
	}

	public function register(int $activityId, int $responsibleId, bool $isComplete): void
	{
		$existedItemId = $this->getIdByActivityId($activityId);

		if ($existedItemId)
		{
			IncomingChannelTable::update($existedItemId, [
				'RESPONSIBLE_ID' => $responsibleId,
				'COMPLETED' => $isComplete,
			]);
		}
		else
		{
			IncomingChannelTable::add([
				'ACTIVITY_ID' => $activityId,
				'RESPONSIBLE_ID' => $responsibleId,
				'COMPLETED' => $isComplete,
			]);
		}
	}

	public function unregister(int $activityId): void
	{
		$existedItemId = $this->getIdByActivityId($activityId);

		if ($existedItemId)
		{
			IncomingChannelTable::delete($existedItemId);
		}
	}

	public function clearCache(): void
	{
		$this->cache = [];
	}

	protected function getIdByActivityId(int $activityId): ?int
	{
		if (!array_key_exists($activityId, $this->cache))
		{
			$existedItem = IncomingChannelTable::query()
				->where('ACTIVITY_ID', $activityId)
				->setSelect(['ID'])
				->setLimit(1)
				->fetch();

			$this->cache[$activityId] = $existedItem ? (int)$existedItem['ID'] : null;
		}

		return $this->cache[$activityId];
	}
}
