<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\OccurredUserTrait;
use Bitrix\Tasks\Internals\Notification\Controller;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\TaskObject;

class SendNotification
{
	use ConfigTrait;
	use OccurredUserTrait;

	public function __invoke(array $fields): void
	{
		$parameters = [
			'SPAWNED_BY_AGENT' => $this->config->isFromAgent(),
			'SPAWNED_BY_WORKFLOW' => $this->config->isFromWorkFlow(),
		];

		$task = TaskRegistry::getInstance()->drop($fields['ID'])->getObject($fields['ID'], true);
		if ($task === null)
		{
			return;
		}

		$controller = new Controller();
		$controller->onTaskCreated($task, $parameters);
		$controller->push();
	}
}
