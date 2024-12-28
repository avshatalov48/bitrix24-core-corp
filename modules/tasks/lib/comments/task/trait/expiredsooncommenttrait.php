<?php

namespace Bitrix\Tasks\Comments\Task\Trait;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Comments\Internals\Comment;
use Bitrix\Tasks\Provider\Employee\EmployeeProvider;

trait ExpiredSoonCommentTrait
{
	private function getExpiredSoonCommentsForMembers(array $memberIds, array $liveParameters): array
	{
		$comments = [];

		if (empty($memberIds))
		{
			$comments[] = $this->getExpiredSoonComment('COMMENT_POSTER_COMMENT_TASK_EXPIRED_SOON_NO_MEMBERS', $liveParameters);
		}

		[$employeeIds, $guestIds] = EmployeeProvider::getInstance()->splitIntoEmployeesAndGuests($memberIds);

		if (!empty($employeeIds))
		{
			$comments[] = $this->getExpiredSoonComment('COMMENT_POSTER_COMMENT_TASK_EXPIRED_SOON', $liveParameters, $employeeIds);

			if (!empty($guestIds))
			{
				$comments[] = $this->getExpiredSoonComment('COMMENT_POSTER_COMMENT_TASK_EXPIRED_SOON_SHORT', $liveParameters, $guestIds);
			}
		}
		elseif (!empty($guestIds))
		{
			$comments[] = $this->getExpiredSoonComment('COMMENT_POSTER_COMMENT_TASK_EXPIRED_SOON_WITHOUT_EFFICIENCY', $liveParameters, $guestIds);
		}

		return $comments;
	}

	private function getExpiredSoonComment(string $messageKey, array $liveParameters, array $userIds = []): Comment
	{
		if (empty($userIds))
		{
			$replace = [];
		}
		else
		{
			$replace = ['#MEMBERS#' => implode(', ', $this->getUsersCodes($userIds))];
		}

		$messageKey = $this->getLastVersionedMessageKey($messageKey);

		return new Comment(
			Loc::getMessage($messageKey, $replace),
			$this->authorId,
			Comment::TYPE_EXPIRED_SOON,
			[[$messageKey, array_merge($replace, $liveParameters)]],
		);
	}
}