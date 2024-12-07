<?php

namespace Bitrix\Tasks\Rest\Controllers\Checklist\Dto;

class TaskCheckListDto
{
	public readonly int $taskId;

	public function setTaskId(int $taskId): static
	{
		$this->taskId = $taskId;
		return $this;
	}
}