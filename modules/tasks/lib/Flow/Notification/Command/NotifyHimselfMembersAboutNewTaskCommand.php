<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage ${SUBPACKAGE}
 * @copyright 2001-2024 Bitrix
 */

namespace Bitrix\Tasks\Flow\Notification\Command;

class NotifyHimselfMembersAboutNewTaskCommand
{
	private int $taskId;
	private int $flowId;

	public function __construct(int $taskId, int $flowId)
	{
		$this->taskId = $taskId;
		$this->flowId = $flowId;
	}

	public function getTaskId(): int
	{
		return $this->taskId;
	}

	public function getFlowId(): int
	{
		return $this->flowId;
	}
}