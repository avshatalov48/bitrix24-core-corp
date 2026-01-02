<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Async\Message;

use Bitrix\Tasks\V2\Internal\Async\AbstractBaseMessage;
use Bitrix\Tasks\V2\Internal\Async\QueueId;

class UpdateSearchIndex extends AbstractBaseMessage
{
	public function __construct(
		public readonly array $task,
	)
	{

	}

	public function jsonSerialize(): array
	{
		return [
			'task' => $this->serialiseDateTime(
				payload: $this->task,
				dateTimeKeys: ['CHANGED_DATE', 'CREATED_DATE'],
			),
		];
	}

	protected function getQueueId(): QueueId
	{
		return QueueId::UpdateSearchIndex;
	}
}
