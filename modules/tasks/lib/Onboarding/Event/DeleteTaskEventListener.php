<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Event;

use Bitrix\Main\EventResult;

final class DeleteTaskEventListener extends AbstractEventListener
{
	public function onTaskDelete(int $taskId): EventResult
	{
		$eventResult = new EventResult(EventResult::SUCCESS);

		$this->deleteByPair($taskId);

		return $eventResult;
	}
}