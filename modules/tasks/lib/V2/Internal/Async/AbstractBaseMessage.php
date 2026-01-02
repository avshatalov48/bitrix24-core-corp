<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Async;

use Bitrix\Main\Messenger\Entity\AbstractMessage;
use Bitrix\Main\Messenger\Entity\MessageInterface;
use Bitrix\Main\Messenger\Entity\ProcessingParam\ItemIdParam;
use Bitrix\Main\Type\DateTime;

abstract class AbstractBaseMessage extends AbstractMessage
{
	protected const TYPE_TASK = 'task';

	abstract protected function getQueueId(): QueueId;

	public function sendByTaskId(int $taskId): void
	{
		$this->sendByItem($taskId, static::TYPE_TASK);
	}

	public function sendByItem(int $itemId, string $itemType): void
	{
		$this->send($this->getQueueId()->value, [new ItemIdParam("{$itemType}_{$itemId}")]);
	}

	public static function createFromData(array $data): MessageInterface
	{
		return new static(...$data);
	}

	public function serialiseDateTime(array $payload, array $dateTimeKeys): array
	{
		foreach ($dateTimeKeys as $dateTimeKey)
		{
			if (isset($payload[$dateTimeKey]) && $payload[$dateTimeKey] instanceof DateTime)
			{
				$payload[$dateTimeKey] = $payload[$dateTimeKey]->getTimestamp();
			}
		}

		return $payload;
	}

	public function unSerialiseDateTime(array $payload, array $dateTimeKeys): array
	{
		foreach ($dateTimeKeys as $dateTimeKey)
		{
			if (isset($payload[$dateTimeKey]) && is_int($payload[$dateTimeKey]))
			{
				$payload[$dateTimeKey] = DateTime::createFromTimestamp($payload[$dateTimeKey]);
			}
		}

		return $payload;
	}

	public function sendByInternalQueueId(): void
	{
		$this->send($this->getQueueId()->value);
	}
}
