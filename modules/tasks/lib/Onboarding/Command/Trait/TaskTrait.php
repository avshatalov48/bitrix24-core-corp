<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Command\Trait;

use Bitrix\Tasks\Internals\TaskObject;

trait TaskTrait
{
	use ContainerTrait;

	private function isOnePersonTask(TaskObject $task): bool
	{
		return $task->getCreatedBy() === $task->getResponsibleId();
	}

	private function isTaskViewedByResponsible(TaskObject $task): bool
	{
		return $this->getContainer()->getViewRepository()->isViewed($task->getId(), $task->getResponsibleId());
	}
}