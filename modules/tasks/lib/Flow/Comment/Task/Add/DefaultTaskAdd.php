<?php

namespace Bitrix\Tasks\Flow\Comment\Task\Add;

use Bitrix\Tasks\Flow\Comment\Task\FlowCommentInterface;
use Bitrix\Tasks\Flow\Comment\UserLinkTrait;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;

class DefaultTaskAdd implements FlowCommentInterface
{
	use UserLinkTrait;

	public function __construct(protected int $taskId)
	{

	}

	public function getPartName(): string
	{
		return 'responsible';
	}

	public function getMessageKey(): string
	{
		return 'COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_FIELD_ASSIGNEE';
	}

	public function getReplaces(): array
	{
		$messageKey = $this->getMessageKey();

		$task = TaskRegistry::getInstance()->getObject($this->taskId);
		if (null === $task)
		{
			return [[$messageKey, []]];
		}

		$responsibleId = $task->getResponsibleId();
		if ($responsibleId <= 0)
		{
			return [[$messageKey, []]];
		}

		$replace = ['#NEW_VALUE#' => $this->getUserBBCodes($responsibleId)];

		return [[$messageKey, $replace]];
	}
}