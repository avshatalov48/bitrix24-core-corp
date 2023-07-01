<?php

namespace Bitrix\Crm\Activity\Provider\Eventable;

use Bitrix\Crm\Model\ActivityPingOffsetsTable;
use Bitrix\Crm\Model\ActivityPingQueueTable;
use Bitrix\Main\Type\DateTime;

class PingQueue extends Base
{
	public function register(int $activityId, bool $isCompleted = false, ?string $deadLine = null): void
	{
		if (empty($deadLine) || $isCompleted)
		{
			$this->unregister($activityId);

			return;
		}

		$offsets = ActivityPingOffsetsTable::getOffsetsByActivityId($activityId);
		if (empty($offsets))
		{
			return;
		}

		$deadLine = DateTime::createFromUserTime($deadLine);
		$offsets = array_values(array_unique($offsets));
		$existedIds = $this->getIdsByActivityId($activityId);
		if (empty($existedIds))
		{
			$this->addToQueue($activityId, $deadLine, $offsets);
		}
		else
		{
			if (count($existedIds) === count($offsets))
			{
				$this->updateQueue($deadLine, $existedIds, $offsets);
			}
			else
			{
				// clear all for activity and add again
				ActivityPingQueueTable::deleteByActivityId($activityId);
				$this->addToQueue($activityId, $deadLine, $offsets);
			}
		}
	}

	public function unregister(int $activityId): void
	{
		ActivityPingQueueTable::deleteByActivityId($activityId);
	}

	protected function getEventNamePrefix(): string
	{
		return ActivityPingQueueTable::getEntity()->getNamespace() . ActivityPingQueueTable::getEntity()->getName();
	}

	private function getIdsByActivityId(int $activityId): array
	{
		if (!array_key_exists($activityId, $this->cache))
		{
			$this->cache[$activityId] = ActivityPingQueueTable::getIdsByActivityId($activityId);
		}

		return $this->cache[$activityId];
	}

	private function addToQueue(int $activityId, DateTime $deadLine, array $offsets): void
	{
		foreach ($offsets as $offset)
		{
			$deadlineClone = clone $deadLine; // to avoid influence of ->add(...) to original $deadLine
			$pingDateTime = $offset <= 0 ? $deadlineClone : $deadlineClone->add('-' . $offset . ' minutes');
			if ($pingDateTime->getTimestamp() > (new DateTime())->getTimestamp())
			{
				ActivityPingQueueTable::add([
					'ACTIVITY_ID' => $activityId,
					'PING_DATETIME' => $pingDateTime,
				]);
			}
		}
	}

	private function updateQueue(DateTime $deadLine, array $existedIds, array $offsets): void
	{
		foreach ($existedIds as $index => $existedId)
		{
			$deadlineClone = clone $deadLine; // to avoid influence of ->add(...) to original $deadLine
			$pingDateTime = $offsets[$index] <= 0 ? $deadlineClone : $deadlineClone->add('-' . $offsets[$index] . ' minutes');
			if ($pingDateTime->getTimestamp() > (new DateTime())->getTimestamp())
			{
				ActivityPingQueueTable::update($existedId, ['PING_DATETIME' => $pingDateTime]);
			}
			else
			{
				ActivityPingQueueTable::delete($existedId);
			}
		}
	}
}
