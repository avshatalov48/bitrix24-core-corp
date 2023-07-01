<?php

namespace Bitrix\Crm\Activity\Provider\Eventable;

use Bitrix\Crm\Model\ActivityPingOffsetsTable;

class PingOffset extends Base
{
	public function register(int $activityId, array $offsets = []): void
	{
		if (empty($offsets))
		{
			return;
		}

		$offsets = array_values(array_unique($offsets));

		$existedIds = $this->getIdsByActivityId($activityId);
		if (empty($existedIds))
		{
			$this->addOffsets($activityId, $offsets);
		}
		elseif (count($existedIds) === count($offsets))
		{
			$this->updateOffsets($existedIds, $offsets);
		}
		else
		{
			// clear all for activity and add again
			ActivityPingOffsetsTable::deleteByActivityId($activityId);
			$this->addOffsets($activityId, $offsets);
		}
	}

	public function unregister(int $activityId): void
	{
		ActivityPingOffsetsTable::deleteByActivityId($activityId);
	}

	public function getOffsetsByActivityId(int $activityId): array
	{
		return array_unique(array_map('intval', ActivityPingOffsetsTable::getOffsetsByActivityId($activityId)));
	}

	protected function getEventNamePrefix(): string
	{
		return ActivityPingOffsetsTable::getEntity()->getNamespace() . ActivityPingOffsetsTable::getEntity()->getName();
	}

	private function getIdsByActivityId(int $activityId): array
	{
		if (!array_key_exists($activityId, $this->cache))
		{
			$this->cache[$activityId] = ActivityPingOffsetsTable::getIdsByActivityId($activityId);
		}

		return $this->cache[$activityId];
	}

	private function addOffsets(int $activityId, array $offsets): void
	{
		foreach ($offsets as $offset)
		{
			ActivityPingOffsetsTable::add([
				'ACTIVITY_ID' => $activityId,
				'OFFSET' => $offset,
			]);
		}
	}

	private function updateOffsets(array $existedIds, array $offsets): void
	{
		foreach ($existedIds as $index => $existedId)
		{
			ActivityPingOffsetsTable::update($existedId, [
				'OFFSET' => $offsets[$index],
			]);
		}
	}
}
