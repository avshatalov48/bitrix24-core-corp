<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Tasks;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;

class TaskDeadlineExpired extends LogMessage
{
	use LogMessageTrait;

	public function getType(): string
	{
		return 'TasksTaskModification';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('TASKS_ON_TASK_EXPIRED_TITLE');
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
			'taskTitle' => $this->getTaskTitleBlock($model, $task),
			'responsible' => $this->getUserBlock($model, Loc::getMessage('TASKS_ON_TASK_EXPIRED_RESPONSIBLE')),
		];
	}
}