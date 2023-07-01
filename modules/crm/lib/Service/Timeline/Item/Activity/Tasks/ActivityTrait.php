<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity\Tasks;

use Bitrix\Crm\Integration\Tasks\TaskPathMaker;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\AssociatedEntityModel;
use Bitrix\Crm\Service\Timeline\Layout\Action\Analytics;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockWithTitle;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Link;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Model\File;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Internals\TaskObject;

trait ActivityTrait
{
	private string $analyticsHit = 'tasks.analytics.hit';

	public function getFiles(): array
	{
		$storageFiles = $this->fetchStorageFiles();
		$files = [];
		foreach ($storageFiles as $file)
		{
			$files[] = new File(
				$file['ID'],
				(int)$file['FILE_ID'],
				trim((string)$file['NAME']),
				(int)$file['SIZE'],
				(string)$file['VIEW_URL'],
				$file['PREVIEW_URL'] ? (string)$file['PREVIEW_URL'] : null
			);
		}

		return $files;
	}

	private function getTaskAction(TaskObject $task, string $analyticsActionName = 'task_view'): JsEvent
	{
		$pathMaker = TaskPathMaker::getPathMaker($task->getId(), $this->getContext()->getUserId());

		$event = new JsEvent('Task:View');
		$event
			->addActionParamString('path', $pathMaker->makeEntityPath())
			->addActionParamInt('taskId', $task->getId())
			->addActionParamString('taskTitle', $task->getTitle())
			->setAnalytics(new Analytics(['scenario' => $analyticsActionName], $this->analyticsHit))
		;

		return $event;
	}

	private function getTaskTitleBlock(AssociatedEntityModel $model, TaskObject $task): ContentBlockWithTitle
	{
		$pathMaker = TaskPathMaker::getPathMaker($task->getId(), $this->getContext()->getUserId());
		$uri = new Uri($pathMaker->makeEntityPath());
		$uri->addParams(['analyticsLabel' => ['scenario' => 'task_view']]);
		$redirect = new Redirect($uri);
		$redirect->setAnalytics(new Analytics(['scenario' => 'task_view'], $this->analyticsHit));

		$titleBlockObject = new ContentBlockWithTitle();
		$titleBlockObject
			->setInline()
			->setScope(ContentBlock::SCOPE_WEB)
			->setTitle(Loc::getMessage('TASKS_TASK_DEAL_TASK_TITLE'))
			->setContentBlock(
				(new Link())
					->setValue($task->getTitle())
					->setAction($redirect)
			);

		return $titleBlockObject;
	}

	private function getTaskTitleBlockMobile(AssociatedEntityModel $model, TaskObject $task): ContentBlockWithTitle
	{
		$titleBlockObject = new ContentBlockWithTitle();
		$titleBlockObject
			->setInline()
			->setScope(ContentBlock::SCOPE_MOBILE)
			->setTitle(Loc::getMessage('TASKS_TASK_DEAL_TASK_TITLE'))
			->setContentBlock(
				(new Link())
					->setValue($task->getTitle())
					->setAction($this->getTaskAction($task))
			);

		return $titleBlockObject;
	}

	private function getUserName(AssociatedEntityModel $model): string
	{
		$data = $model->get('SETTINGS');
		$userId = $data['AUTHOR_ID'];

		return $this->getFormattedUserName($userId);
	}

	private function getUserAction(AssociatedEntityModel $model): Redirect
	{
		$data = $model->get('SETTINGS');
		$userId = $data['AUTHOR_ID'];

		return new Redirect(new Uri($this->getUserUrl($userId)));
	}

	private function getFormattedUserName(?int $userId): string
	{
		if (is_null($userId))
		{
			return '';
		}

		return Container::getInstance()->getUserBroker()->getName($userId);
	}

	private function getUserUrl(?int $userId): string
	{
		if (is_null($userId))
		{
			return '';
		}

		return Container::getInstance()->getUserBroker()->getById($userId)['SHOW_URL'];
	}

	private function getTask(AssociatedEntityModel $model, bool $withRelations = true): ?TaskObject
	{
		$taskId = $model->get('SETTINGS')['TASK_ID'] ?? null;
		if (is_null($taskId))
		{
			return null;
		}

		return \Bitrix\Crm\Integration\Tasks\TaskObject::getObject($taskId, $withRelations);
	}
}