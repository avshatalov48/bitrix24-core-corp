<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Tasks;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;

class TaskCommentsViewed extends LogMessage
{
	use LogMessageTrait;

	public function getType(): string
	{
		return 'TasksTaskModification';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('TASKS_ON_TASK_ALL_COMMENT_VIEWED_TITLE');
	}

	public function getIconCode(): ?string
	{
		return Icon::TASK_VIEWED_COMMENT;
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
			'Task' => $this->getTaskTitleBlock($model, $task),
		];
	}
}