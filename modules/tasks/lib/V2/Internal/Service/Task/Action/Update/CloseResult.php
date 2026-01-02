<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\OccurredUserTrait;
use Bitrix\Tasks\Internals\Task\Status;

class CloseResult
{
	use ConfigTrait;
	use OccurredUserTrait;

	public function __invoke(array $fullTaskData): void
	{
		if (in_array((int)$fullTaskData['STATUS'], [Status::COMPLETED, Status::SUPPOSEDLY_COMPLETED], true))
		{
			$taskId = (int)$fullTaskData['ID'];
			$userId = $this->getOccurredUserId($this->config->getUserId());

			Container::getInstance()
				->getResultService()
				->close($taskId, $userId)
			;
		}
	}
}