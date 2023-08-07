<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Tasks;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Link;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Internals\TaskObject;

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
			'responsible' => $this->getResponsibleBlock($task, Loc::getMessage('TASKS_ON_TASK_EXPIRED_RESPONSIBLE')),
		];
	}

	private function getResponsibleBlock(TaskObject $task, string $message): LineOfTextBlocks
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
					->setValue($this->getResponsibleUserName($task))
					->setAction($this->getResponsibleUserAction($task))
			)
		;

		return $clientBlockObject;
	}

	private function getResponsibleUserName(TaskObject $task): string
	{
		$responsibleId = $task->getResponsibleMemberId();
		if (is_null($responsibleId))
		{
			return '';
		}

		return Container::getInstance()->getUserBroker()->getName($responsibleId);
	}

	private function getResponsibleUserAction(TaskObject $task): Redirect
	{
		$responsibleId = $task->getResponsibleMemberId();
		if (is_null($responsibleId))
		{
			return new Redirect(new Uri(''));
		}

		$url = Container::getInstance()->getUserBroker()->getById($responsibleId)['SHOW_URL'];

		return new Redirect(new Uri($url ?? ''));
	}
}