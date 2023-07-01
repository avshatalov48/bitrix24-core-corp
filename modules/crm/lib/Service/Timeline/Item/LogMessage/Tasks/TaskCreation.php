<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Tasks;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;

class TaskCreation extends LogMessage
{
	use LogMessageTrait;

	public function getType(): string
	{
		return 'TasksTaskCreation';
	}

	public function getTitle(): ?string
	{
		$model = $this->getHistoryItemModel();
		$restored = $model->get('TASK_RESTORED') ?? false;
		$code = $restored ? 'TASKS_ON_TASK_ADDED_RESTORED_TITLE' : 'TASKS_ON_TASK_ADDED_TITLE';

		return Loc::getMessage($code);
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
			'contentTaskTitle' => $this->getTaskTitleBlock($model, $task),
		];
	}
}