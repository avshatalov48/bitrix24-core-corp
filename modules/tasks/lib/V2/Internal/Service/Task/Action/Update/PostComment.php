<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Tasks\Comments\Internals\Comment;
use Bitrix\Tasks\Comments\Task\CommentPoster;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\OccurredUserTrait;

class PostComment
{
	use ConfigTrait;
	use OccurredUserTrait;

	public function __invoke(array $fields, array $sourceTaskData, array $changes): void
	{
		if ($this->config->isSkipComments())
		{
			return;
		}

		$taskId = (int)$sourceTaskData['ID'];

		$fieldsForComments = [
			'STATUS',
			'CREATED_BY',
			'RESPONSIBLE_ID',
			'ACCOMPLICES',
			'AUDITORS',
			'DEADLINE',
			'GROUP_ID',
			'FLOW_ID',
		];
		$changesForUpdate = array_intersect_key($changes, array_flip($fieldsForComments));

		if (empty($changesForUpdate))
		{
			return;
		}

		$commentPoster = CommentPoster::getInstance($taskId, $this->getOccurredUserId($this->config->getUserId()));
		if ($commentPoster)
		{
			if (!($isDeferred = $commentPoster->getDeferredPostMode()))
			{
				$commentPoster->enableDeferredPostMode();
			}

			$commentPoster->postCommentsOnTaskUpdate($sourceTaskData, $fields, $changesForUpdate);
			$isCommentAdded =
				$commentPoster->getCommentByType(Comment::TYPE_UPDATE)
				|| $commentPoster->getCommentByType(Comment::TYPE_STATUS);

			$this->config->getRuntime()->setCommentAdded($isCommentAdded);

			if (!$isDeferred)
			{
				$commentPoster->disableDeferredPostMode();
				$commentPoster->postComments();
				$commentPoster->clearComments();
			}
		}
	}
}