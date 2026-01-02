<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Event;

use Bitrix\Main\EventResult;
use Bitrix\Tasks\Internals\Task\Event\View\OnTaskFirstViewEvent;

/**
 * @method EventResult onTaskAdd(int $taskId, array $fields)
 * @method EventResult onTaskDelete(int $taskId)
 * @method EventResult onTaskExpiredSoon(int $taskId, array $task)
 * @method EventResult onTaskUpdate(int $taskId, array $changedFields, array $previousFields)
 * @method EventResult onTaskView(OnTaskFirstViewEvent $event)
 * @method EventResult OnUserInitialize(array $data)
 * @method EventResult onAfterUserDelete(int $userId)
 * @method EventResult onAfterUserFired(array $data)
 */
class FakeEventListener extends AbstractEventListener
{
	public function __call(string $name, array $args): EventResult
	{
		return new EventResult(EventResult::SUCCESS);
	}
}