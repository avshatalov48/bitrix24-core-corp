<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation;

use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Trait\ConfigTrait;

class DeleteResults
{
	use ConfigTrait;

	public function __invoke(array $fullTaskData): void
	{
		$taskId = (int)$fullTaskData['TASK_ID'];
		$userId = $this->config->getUserId();

		Container::getInstance()
			->getResultService()
			->deleteByTaskId(
				$taskId,
				$userId,
			);
	}
}
