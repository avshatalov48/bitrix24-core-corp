<?php

namespace Bitrix\ImOpenLines\V2\Queue;

use Bitrix\Im\V2\Registry;
use Bitrix\Im\V2\Rest\RestConvertible;
use Bitrix\ImOpenLines\Model\ConfigTable;
use Bitrix\ImOpenLines\Model\EO_Config_Collection;

/**
 * @extends Registry<QueueItem>
 */
class Queue extends Registry implements RestConvertible
{
	public static function getQueues(): self
	{
		$queue = new self();
		$queueEntities = self::getQueueEntities();

		foreach ($queueEntities as $entity)
		{
			$queueItem = new QueueItem();
			$queueItem
				->setId($entity->getId())
				->setName($entity->getLineName())
				->setType($entity->getQueueType())
				->setIsActive($entity->getActive())
			;
			$queue[] = $queueItem;
		}

		return $queue;
	}

	protected static function getQueueEntities(): EO_Config_Collection
	{
		$query = ConfigTable::query()
			->setSelect(['ID', 'LINE_NAME', 'ACTIVE', 'QUEUE_TYPE'])
		;

		return $query->fetchCollection();
	}

	public static function getRestEntityName(): string
	{
		return 'queueItems';
	}

	public function toRestFormat(array $option = []): ?array
	{
		$rest = [];

		foreach ($this as $item)
		{
			$rest[] = $item->toRestFormat();
		}

		return $rest;
	}
}