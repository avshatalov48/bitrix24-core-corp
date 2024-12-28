<?php

namespace Bitrix\Tasks\Flow\Comment\Task;

use Bitrix\Tasks\Flow\Comment\CommentEvent;
use Bitrix\Tasks\Flow\Comment\Task\Add\DefaultTaskAdd;
use Bitrix\Tasks\Flow\Comment\Task\Add\HimselfFlowTaskAdd;
use Bitrix\Tasks\Flow\Comment\Task\Add\ManuallyFlowTaskAdd;
use Bitrix\Tasks\Flow\Flow;

class FlowCommentFactory
{
	public static function get(Flow $flow, int $taskId, CommentEvent $event): FlowCommentInterface
	{
		return match (true)
		{
			$event === CommentEvent::TASK_ADD && $flow->isManually() => new ManuallyFlowTaskAdd($taskId),
			$event === CommentEvent::TASK_ADD && $flow->isHimself() => new HimselfFlowTaskAdd($taskId, $flow),
			default => new DefaultTaskAdd($taskId),
		};
	}
}