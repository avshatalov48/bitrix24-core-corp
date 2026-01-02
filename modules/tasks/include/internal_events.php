<?php

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;
use Bitrix\Tasks\Flow\Integration\AI\Copilot\RequestSender;
use Bitrix\Tasks\Flow\Integration\AI\Event\EfficiencyListener;
use Bitrix\Tasks\Internals\Task\Event\View\OnTaskFirstViewEvent;
use Bitrix\Tasks\Onboarding\Event\AddTaskEventListener;
use Bitrix\Tasks\Onboarding\Event\DeleteTaskEventListener;
use Bitrix\Tasks\Onboarding\Event\ExpiredSoonEventListener;
use Bitrix\Tasks\Onboarding\Event\UpdateTaskEventListener;
use Bitrix\Tasks\Onboarding\Event\ViewTaskEventListener;
use Bitrix\Tasks\V2\Internal\EventHandler\Reminder;

$eventManager = EventManager::getInstance();

// region flow copilot
$eventManager->addEventHandler(
	'tasks',
	'onFlowEfficiencyChanged',
	static fn (Event $event): EventResult
		=> (new EfficiencyListener())->onFlowEfficiencyChanged($event)
);

$eventManager->addEventHandler(
	'tasks',
	'onFlowDataCollected',
	static fn (Event $event): EventResult
		=> (new RequestSender())->onFlowDataCollected($event)
);

$eventManager->addEventHandler(
	'tasks',
	'onAfterTasksFlowDelete',
	static fn (Event $event): EventResult
		=> ServiceLocator::getInstance()->get('tasks.flow.copilot.advice.service')->onFlowDeleted($event)
);

$eventManager->addEventHandler(
	'tasks',
	'onAfterTasksFlowDelete',
	static fn (Event $event): EventResult
		=> ServiceLocator::getInstance()->get('tasks.flow.copilot.collected.data.service')->onFlowDeleted($event)
);
// endregion

// region onboarding
$eventManager->addEventHandler(
	'tasks',
	'onTaskAdd',
	static fn (int $taskId, array $fields): EventResult
		=> AddTaskEventListener::getInstance()->onTaskAdd($taskId, $fields)
);

$eventManager->addEventHandler(
	'tasks',
	'onTaskFirstView',
	static fn (OnTaskFirstViewEvent $event): EventResult
		=> ViewTaskEventListener::getInstance()->onTaskView($event)
);

$eventManager->addEventHandler(
	'tasks',
	'onTaskUpdate',
	static fn (int $taskId, array $changedFields, array $previousFields): EventResult
		=> UpdateTaskEventListener::getInstance()->onTaskUpdate($taskId, $changedFields, $previousFields)
);

$eventManager->addEventHandler(
	'tasks',
	'onTaskDelete',
	static fn (int $taskId): EventResult
		=> DeleteTaskEventListener::getInstance()->onTaskDelete($taskId)
);

$eventManager->addEventHandler(
	'tasks',
	'onTaskExpiredSoon',
	static fn (Event $event): EventResult
		=> ExpiredSoonEventListener::getInstance()->onTaskExpiredSoon(
			$event->getParameter('TASK_ID'),
			$event->getParameter('TASK')
	)
);
// endregion

// region reminder
$eventManager->addEventHandler(
	'tasks',
	'onTaskUpdateInternal',
	static function (Event $event): void
	{
		$before = $event->getParameter('before');
		$after = $event->getParameter('after');

		Reminder::onTaskUpdate($before, $after);
	}
);
// endregion