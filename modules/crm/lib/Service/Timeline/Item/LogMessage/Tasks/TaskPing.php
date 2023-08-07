<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Tasks;

use Bitrix\Crm\Integration\Tasks\TaskObject;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Date;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Main\Type\DateTime;

class TaskPing extends LogMessage\Ping
{
	use LogMessageTrait;

	public function getType(): string
	{
		return 'TasksTaskModification';
	}

	public function buildContentBlocks(): array
	{
		$taskId = (int)$this->entityModel->get('SETTINGS')['TASK_ID'] ?? null;
		$deadline = DateTime::createFromUserTime($this->entityModel->get('DEADLINE'));

		if (!$taskId)
		{
			return [];
		}
		$task = TaskObject::getObject($taskId);
		if (is_null($task))
		{
			return [];
		}

		$contentLine = new LineOfTextBlocks();
		$linkWithTitle = $this->getTaskTitleBlock($this->getHistoryItemModel(), $task);
		$textWithDeadLine = (new Date())->setDate($deadline);
		$contentLine->addContentBlock('title', $linkWithTitle);
		$contentLine->addContentBlock('deadline', $textWithDeadLine);

		return [
			'ContentBlock' => $contentLine,
		];
	}
}