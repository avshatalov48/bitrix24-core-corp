<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Async\Message;

use Bitrix\Tasks\V2\Internal\Async\AbstractBaseMessage;
use Bitrix\Tasks\V2\Internal\Async\QueueId;

class AddScenario extends AbstractBaseMessage
{
	public function __construct(
		public readonly int $taskId,
		public readonly array $scenarios,
	)
	{

	}

	protected function getQueueId(): QueueId
	{
		return QueueId::AddScenario;
	}

	public function jsonSerialize(): array
	{
		return [
			'taskId' => $this->taskId,
			'scenarios' => $this->scenarios,
		];
	}
}