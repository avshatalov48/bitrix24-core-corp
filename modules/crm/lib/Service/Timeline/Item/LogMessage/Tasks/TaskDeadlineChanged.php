<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Tasks;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ValueChange;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ValueChangeItem;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;

class TaskDeadlineChanged extends LogMessage
{
	use LogMessageTrait;

	public function getType(): string
	{
		return 'TasksTaskModification';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('TASKS_ON_TASK_DEADLINE_CHANGED_TITLE');
	}

	public function getIconCode(): ?string
	{
		return Icon::INFO;
	}

	public function getContentBlocks(): ?array
	{
		$model = $this->getHistoryItemModel();
		$from = (new ValueChangeItem())->setPillText($model->get('TASK_PREV_DEADLINE'));
		$to = (new ValueChangeItem())->setPillText($model->get('TASK_CURR_DEADLINE'));
		$task = $this->getTask($model);
		if (is_null($task))
		{
			return null;
		}

		return [
			'contentTaskTitle' => $this->getTaskTitleBlock($model, $task),
			'contentTaskDeadlineChange' => (new ValueChange())
				->setFrom($from)
				->setTo($to)
		];
	}
}