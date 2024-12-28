<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Bizproc;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Item\Bizproc\Workflow;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Timeline\Item\Bizproc\TaskBlockData;

class TaskCompleted extends LogMessage
{
	use Workflow;

	public function getType(): string
	{
		return 'BizprocTaskCompleted';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_LOG_MESSAGE_BIZPROC_TASK_COMPLETED');
	}

	public function getIconCode(): string
	{
		return Icon::INFO;
	}

	public function getContentBlocks(): ?array
	{
		$result = [];

		$settings = $this->getModel()->getSettings();
		$workflowId = $settings['WORKFLOW_ID'] ?? null;
		$users = $settings['USERS'] ?? null;
		$templateName = $settings['WORKFLOW_TEMPLATE_NAME'] ?? null;

		if (empty($workflowId) || empty($users) || empty($templateName))
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

		$assignedBlock = $this->buildAssignedBlock(
			$workflowId,
			$users,
		);
		if (isset($assignedBlock))
		{
			$result['assignedBlock'] = $assignedBlock;
		}

		return $result;
	}

	public function getTags(): array
	{
		return [
			'status' => $this->getStatusBlock(),
		];
	}

	private function getStatusBlock(): Tag
	{
		return new Tag(
			Loc::getMessage('CRM_LOG_MESSAGE_BIZPROC_TASK_COMPLETED_STATUS') ?? '',
			Tag::TYPE_SECONDARY
		);
	}
}
