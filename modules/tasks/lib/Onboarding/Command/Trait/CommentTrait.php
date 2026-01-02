<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Command\Trait;

use Bitrix\Tasks\Comments\Internals\Comment;
use Bitrix\Tasks\Comments\Task\CommentPoster;
use Bitrix\Tasks\Internals\TaskObject;

trait CommentTrait
{
	private function postComment(TaskObject $task, Comment $comment): void
	{
		$poster = CommentPoster::getInstance($task->getId(), $task->getResponsibleId());
		if ($poster === null)
		{
			return;
		}

		$poster->clearComments();
		$poster->addComments([$comment]);
		$poster->disableDeferredPostMode();
		$poster->postComments();
	}
}