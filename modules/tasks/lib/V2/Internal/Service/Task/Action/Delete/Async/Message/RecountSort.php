<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Async\Message;

use Bitrix\Tasks\V2\Internal\Async\AbstractBaseMessage;
use Bitrix\Tasks\V2\Internal\Async\QueueId;

class RecountSort extends AbstractBaseMessage
{
	public function __construct(
		public readonly int $taskId
	)
	{

	}

	protected function getQueueId(): QueueId
	{
		return QueueId::RecountSort;
	}

	public function jsonSerialize(): array
	{
		return [
			'taskId' => $this->taskId
		];
	}
}