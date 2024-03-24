<?php

namespace Bitrix\Tasks\Copy;

use Bitrix\Tasks\Copy\Implement\OrdinaryTask;

class OrdinaryTaskManager extends TaskManager
{
	private int $parentTaskId = 0;

	public function getImplementerClass(): string
	{
		return OrdinaryTask::class;
	}

	public function setParentTaskId(int $taskId): static
	{
		$this->parentTaskId = $taskId;
		return $this;
	}

	protected function getTaskImplementer(): OrdinaryTask
	{
		/** @var OrdinaryTask $implementer */
		$implementer = parent::getTaskImplementer();

		return $implementer
			->setParentTaskId($this->parentTaskId);
	}
}