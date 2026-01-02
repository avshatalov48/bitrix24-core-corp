<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Event;

use Bitrix\Main\EventResult;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\Event\View\OnTaskFirstViewEvent;
use Bitrix\Tasks\Onboarding\Internal\Type;

final class ViewTaskEventListener extends AbstractEventListener
{
	public function onTaskView(OnTaskFirstViewEvent $event): EventResult
	{
		$eventResult = new EventResult(EventResult::SUCCESS);

		$taskId = $event->getTaskId();
		$userId = $event->getUserId();

		$task = TaskRegistry::getInstance()->getObject($taskId);
		if ($task === null)
		{
			return $eventResult;
		}

		if ($task->getResponsibleId() !== $userId)
		{
			return $eventResult;
		}

		$this->deleteByUserJob(
			[
				Type::OneDayNotViewed,
				Type::TwoDaysNotViewed,
				Type::TooManyTasks,
				Type::ResponsibleInvitationNotAcceptedOneDay,
				Type::ResponsibleInvitationAccepted,
				Type::InvitedResponsibleNotViewTaskTwoDays,
			],
			$userId,
			$taskId,
		);

		return new EventResult(EventResult::SUCCESS);
	}
}