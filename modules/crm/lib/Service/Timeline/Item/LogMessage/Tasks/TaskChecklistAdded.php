<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Tasks;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;

class TaskChecklistAdded extends LogMessage
{
	use LogMessageTrait;

	public function getType(): string
	{
		return 'TasksTaskModification';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('TASKS_ON_TASK_CHECKLIST_ADDED_TITLE');
	}

	public function getIconCode(): ?string
	{
		return Icon::INFO;
	}

	public function getContentBlocks(): ?array
	{
		$model = $this->getHistoryItemModel();
		$task = $this->getTask($model);
		if (is_null($task))
		{
			return null;
		}

		return [
			'ContentBlock' => $this->getTaskTitleBlock($model, $task),
			'client' => $this->getUserBlock($model, Loc::getMessage('TASKS_ON_TASK_CHECKLIST_ADDED_CHANGE')),
		];
	}
}