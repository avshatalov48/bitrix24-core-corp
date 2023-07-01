<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Tasks;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ValueChange;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ValueChangeItem;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;

class TaskDisapproved extends LogMessage
{
	use LogMessageTrait;

	public function getType(): string
	{
		return 'TasksTaskModification';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('TASKS_ON_TASK_DISAPPROVED_TITLE');
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

		$from = (new ValueChangeItem())->setPillText(
			Loc::getMessage('TASKS_ON_TASK_STATUS_CHANGED_STATUS_' . $model->get('TASK_PREVIOUS_STATUS') ?? '')
		);
		$to = (new ValueChangeItem())->setPillText(
			Loc::getMessage('TASKS_ON_TASK_STATUS_CHANGED_STATUS_' . $model->get('TASK_CURRENT_STATUS') ?? '')
		);

		return [
			'contentTaskTitle' => $this->getTaskTitleBlock($model, $task),
			'contentTaskDeadlineChange' => (new ValueChange())
				->setFrom($from)
				->setTo($to)
		];
	}
}