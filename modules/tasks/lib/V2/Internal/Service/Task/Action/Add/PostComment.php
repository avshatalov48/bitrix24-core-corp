<?php

declare(strict_types=1);


namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

use Bitrix\Tasks\Comments\Internals\Comment;
use Bitrix\Tasks\Comments\Task\CommentPoster;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\OccurredUserTrait;

class PostComment
{
	use ConfigTrait;
	use OccurredUserTrait;

	public function __invoke(array $fields, array $fullTaskData): bool
	{
		$mergedFields = array_merge($fullTaskData, $fields);

		$commentPoster = CommentPoster::getInstance($fields['ID'], $this->getOccurredUserId($this->config->getUserId()));
		if (!$commentPoster)
		{
			return false;
		}

		if (!($isDeferred = $commentPoster->getDeferredPostMode()))
		{
			$commentPoster->enableDeferredPostMode();
		}

		$commentPoster->postCommentsOnTaskAdd($mergedFields);
		$isCommentAdded = (bool)$commentPoster->getCommentByType(Comment::TYPE_ADD);

		$this->config->getRuntime()->setIsCommentAdded($isCommentAdded);

		if (!$isDeferred)
		{
			$commentPoster->disableDeferredPostMode();
			$commentPoster->postComments();
			$commentPoster->clearComments();
		}

		return $isCommentAdded;
	}
}