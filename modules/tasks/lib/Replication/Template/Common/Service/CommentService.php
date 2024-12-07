<?php

namespace Bitrix\Tasks\Replication\Template\Common\Service;

use Bitrix\Main\Result;
use Bitrix\Tasks\Comments\Task\CommentPoster;
use Bitrix\Tasks\Internals\TaskObject;

class CommentService
{
	public function __construct(private TaskObject $task, private int $userId)
	{
	}

	public function clear(): Result
	{
		$commentPoster = CommentPoster::getInstance($this->task->getId(), $this->userId);
		$commentPoster->enableDeferredPostMode();
		$commentPoster->clearComments();

		return new Result();
	}
}