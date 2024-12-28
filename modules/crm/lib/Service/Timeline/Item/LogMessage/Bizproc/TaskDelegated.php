<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Bizproc;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Item\Bizproc\Workflow;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Timeline\Item\Bizproc\TaskBlockData;

class TaskDelegated extends LogMessage
{
	use Workflow;

	public function getType(): string
	{
		return 'BizprocTaskDelegated';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_LOG_MESSAGE_BIZPROC_TASK_DELEGATED');
	}

	public function getIconCode(): ?string
	{
		return Icon::INFO;
	}

	public function getContentBlocks(): ?array
	{
		$result = [];

		$settings = $this->getModel()->getSettings();
		$workflowId = $settings['WORKFLOW_ID'] ?? null;
		$users = $settings['USERS'] ?? null;
		$usersRemoved = $settings['USERS_REMOVED'] ?? null;
		$templateName = $settings['WORKFLOW_TEMPLATE_NAME'] ?? null;

		if (empty($workflowId) || empty($users) || empty($templateName) || empty($usersRemoved))
		{
			return null;
		}

		$processNameBlock = $this->buildProcessNameBlock($templateName, $workflowId);
		if (isset($processNameBlock))
		{
			$result['processNameBlock'] = $processNameBlock;
		}

		$taskData = new TaskBlockData(
			(int)($settings['TASK_ID'] ?? 0),
			(string)($settings['TASK_NAME'] ?? ''),
		);
		$taskBlock = $this->buildTaskBlock($taskData);
		if (isset($taskBlock))
		{
			$result['taskBlock'] = $taskBlock;
		}

		$delegatedBlock = $this->buildAssignedBlock(
			$workflowId,
			$usersRemoved,
			Loc::getMessage('CRM_LOG_MESSAGE_BIZPROC_TASK_DELEGATE_FROM')
		);
		if (isset($delegatedBlock))
		{
			$result['delegatedBlock'] = $delegatedBlock;
		}

		$assignedBlock = $this->buildAssignedBlock(
			$workflowId,
			$users,
			Loc::getMessage('CRM_LOG_MESSAGE_BIZPROC_TASK_DELEGATE_TO')
		);
		if (isset($assignedBlock))
		{
			$result['assignedBlock'] = $assignedBlock;
		}

		return $result;
	}
}
