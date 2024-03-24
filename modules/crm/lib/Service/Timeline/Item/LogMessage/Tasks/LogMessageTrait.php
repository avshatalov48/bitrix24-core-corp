<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Tasks;

use Bitrix\Crm\Integration\Tasks\TaskObject;
use Bitrix\Crm\Integration\Tasks\TaskPathMaker;
use Bitrix\Crm\Service\Timeline\Item\HistoryItemModel;
use Bitrix\Crm\Service\Timeline\Layout\Action\Analytics;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Link;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\TextPropertiesInterface;
use Bitrix\Main\Web\Uri;

trait LogMessageTrait
{
	private function getUserName(HistoryItemModel $model): string
	{
		return $model->get('AUTHOR')['FORMATTED_NAME'] ?? '';
	}

	private function getUserAction(HistoryItemModel $model): Redirect
	{
		return new Redirect(new Uri($model->get('AUTHOR')['SHOW_URL'] ?? ''));
	}

	private function getTaskAction(\Bitrix\Tasks\Internals\TaskObject $task): JsEvent
	{
		$uri = $this->getUri($task);

		$event = new JsEvent('Task:View');
		$event
			->addActionParamString('path', $uri->getUri())
			->addActionParamInt('taskId', $task->getId())
			->addActionParamString('taskTitle', $task->getTitle())
		;

		return $event;
	}

	private function getTaskTitleBlock(HistoryItemModel $model, \Bitrix\Tasks\Internals\TaskObject $task): TextPropertiesInterface
	{
		$contentBlockObject = new Link();
		$contentBlockObject
			->setValue($task->getTitle())
			->setAction($this->getTaskAction($task))
		;

		return $contentBlockObject;
	}

	private function getUserBlock(HistoryItemModel $model, string $message): LineOfTextBlocks
	{
		$clientBlockObject = new LineOfTextBlocks();
		$clientBlockObject
			->addContentBlock(
				'changed',
				(new Text())
					->setValue($message)
			)
			->addContentBlock(
				'changedBy',
				(new Link())
					->setValue($this->getUserName($model))
					->setAction($this->getUserAction($model))
			)
		;

		return $clientBlockObject;
	}

	public function getTask(HistoryItemModel $model, bool $withRelations = true): ?\Bitrix\Tasks\Internals\TaskObject
	{
		$taskId = $model->get('TASK_ID');
		if (is_null($taskId))
		{
			return null;
		}

		return TaskObject::getObject($taskId, $withRelations);
	}

	/**
	 * @param \Bitrix\Tasks\Internals\TaskObject $task
	 * @return Uri
	 */
	private function getUri(\Bitrix\Tasks\Internals\TaskObject $task): Uri
	{
		$pathMaker = TaskPathMaker::getPathMaker($task->getId(), $this->getContext()->getUserId());
		$uri = new Uri($pathMaker->makeEntityPath());
		$ownerName = strtolower(\CCrmOwnerType::ResolveName($this->getContext()->getEntityTypeId()));
		$uri->addParams([
			'ta_sec' => 'crm',
			'ta_sub' => $ownerName,
			'ta_el' => 'title_click',
		]);

		return $uri;
	}
}