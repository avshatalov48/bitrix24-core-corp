<?php

declare(strict_types=1);


namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\OccurredUserTrait;
use Bitrix\Tasks\Internals\Notification\Controller;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\TaskObject;

class SendNotification
{
	use ConfigTrait;
	use OccurredUserTrait;

	public function __invoke(array $fields, array $fullTaskData, array $sourceTaskData, TaskObject $task): void
	{
		if ($this->config->isSkipNotifications())
		{
			return;
		}

		$notificationFields = array_merge($fields, ['CHANGED_BY' => $this->getOccurredUserId($this->config->getUserId())]);
		$statusChanged = $fullTaskData['STATUS_CHANGED'] ?? false;

		$controller = new Controller();

		if ($statusChanged)
		{
			$status = (int)$fullTaskData['REAL_STATUS'];

			$controller->onTaskStatusChanged($task, $status, $notificationFields);
		}

		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/tasks/classes/general/tasknotifications.php');

		$task = TaskRegistry::getInstance()
			->drop($task->getId())
			->getObject($task->getId(), true);

		$controller->onTaskUpdated($task, $notificationFields, $sourceTaskData, ['spawned_by_agent' => false]);
		$controller->push();
	}
}