<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Tasks;

use Bitrix\Crm\Integration\Tasks\TaskPathMaker;
use Bitrix\Crm\Service\Timeline\Item\HistoryItemModel;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Link;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\TextPropertiesInterface;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;

class TaskResultAdded extends LogMessage
{
	use LogMessageTrait;

	public function getType(): string
	{
		return 'TasksTaskModification';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('TASKS_ON_TASK_RESULT_ADDED_TITLE');
	}

	public function getIconCode(): ?string
	{
		return Icon::TASK;
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
			'Footer' => $this->getFooterBlock($model, $task),
		];
	}

	public function getFooterBlock(HistoryItemModel $model, \Bitrix\Tasks\Internals\TaskObject $task): TextPropertiesInterface
	{
		$result = $task->getLastResult();
		$resultParams = is_null($result) ? [] : ['RID' => $result->getId()];

		$footerBlockObject = new Link();
		$footerBlockObject
			->setValue(Loc::getMessage('TASKS_ON_TASK_RESULT_ADDED_VIEW_RESULT'))
			->setAction($this->getTaskResultAction($task))
		;

		return $footerBlockObject;
	}

	private function getTaskResultAction(\Bitrix\Tasks\Internals\TaskObject $task): JsEvent
	{
		$result = $task->getLastResult();
		if (is_null($result))
		{
			return $this->getTaskAction($task);
		}
		$pathMaker = TaskPathMaker::getPathMaker($task->getId(), $this->getContext()->getUserId());

		$event = new JsEvent('Task:ResultView');
		$event
			->addActionParamString('path', $pathMaker->makeEntityPath())
			->addActionParamInt('taskId', $task->getId())
			->addActionParamString('taskTitle', $task->getTitle())
		;

		return $event;
	}
}