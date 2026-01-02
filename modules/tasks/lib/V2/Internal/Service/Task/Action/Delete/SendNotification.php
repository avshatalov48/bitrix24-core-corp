<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Trait\ConfigTrait;
use Bitrix\Tasks\Internals\Notification\Controller;
use Bitrix\Tasks\Internals\TaskObject;

class SendNotification
{
	use ConfigTrait;

	public function __invoke(TaskObject $task): void
	{
		$controller = new Controller();
		$controller->onTaskDeleted($task, $this->config->getRuntime()->isMovedToRecyclebin());
		$controller->push();
	}
}