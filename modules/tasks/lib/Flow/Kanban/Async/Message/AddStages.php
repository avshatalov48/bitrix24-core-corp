<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Kanban\Async\Message;

use Bitrix\Main\Messenger\Entity\MessageInterface;
use Bitrix\Tasks\V2\Internal\Async\AbstractBaseMessage;
use Bitrix\Tasks\V2\Internal\Async\QueueId;

final class AddStages extends AbstractBaseMessage
{
	public function __construct(
		public readonly int $projectId,
		public readonly int $ownerId,
		public readonly int $flowId,
	)
	{
	}

	public static function createFromData(array $data): MessageInterface
	{
		return new self(...$data);
	}

	public function jsonSerialize(): array
	{
		return [
			'projectId' => $this->projectId,
			'ownerId' => $this->ownerId,
			'flowId' => $this->flowId,
		];
	}

	protected function getQueueId(): QueueId
	{
		return QueueId::AddFlowStages;
	}
}