<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Tasks;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ValueChange;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ValueChangeItem;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;

class TaskStatusChanged extends LogMessage
{
	use LogMessageTrait;

	public function getType(): string
	{
		return 'TasksTaskModification';
	}

	public function getTitle(): ?string
	{
		$model = $this->getHistoryItemModel();
		$showBackToWorkTitle = $model->get('SHOW_RETURNED_BACK_TO_WORK_TITLE') ?? false;

		return $showBackToWorkTitle
			? Loc::getMessage('TASKS_ON_TASK_STATUS_RETURNED_BACK_TO_WORK_TITLE')
			: Loc::getMessage('TASKS_ON_TASK_STATUS_CHANGED_TITLE');
	}

	public function getIconCode(): ?string
	{
		return Icon::STAGE_CHANGE;
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