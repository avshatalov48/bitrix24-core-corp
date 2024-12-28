<?php

namespace Bitrix\Tasks\Flow\Comment\Task\Add;

use Bitrix\Tasks\Flow\Comment\Task\FlowCommentInterface;
use Bitrix\Tasks\Flow\Comment\UserLinkTrait;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;

class ManuallyFlowTaskAdd implements FlowCommentInterface
{
	use UserLinkTrait;

	public function __construct(protected int $taskId)
	{

	}

	public function getPartName(): string
	{
		return 'flow';
	}

	public function getMessageKey(): string
	{
		return 'COMMENT_POSTER_COMMENT_TASK_ADD_TO_FLOW_WITH_MANUAL_DISTRIBUTION';
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

		$messageKey = 'COMMENT_POSTER_COMMENT_TASK_ADD_TO_FLOW_WITH_MANUAL_DISTRIBUTION';
		$replace = ['#RESPONSIBLE#' => $this->getUserBBCodes($responsibleId)];

		return [[$messageKey, $replace]];
	}
}