<?php

namespace Bitrix\Tasks\Comments\Task\Trait;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Comments\Internals\Comment;
use Bitrix\Tasks\Provider\Employee\EmployeeProvider;

trait ExpiredCommentTrait
{
	private function getExpiredCommentsForMembers(array $memberIds, array $liveParameters): array
	{
		$comments = [];

		if (empty($memberIds))
		{
			$comments[] = $this->getExpiredComment('COMMENT_POSTER_COMMENT_TASK_EXPIRED_NO_MEMBERS', $liveParameters);
		}

		[$employeeIds, $guestIds] = EmployeeProvider::getInstance()->splitIntoEmployeesAndGuests($memberIds);

		if (!empty($employeeIds))
		{
			$comments[] = $this->getExpiredComment('COMMENT_POSTER_COMMENT_TASK_EXPIRED', $liveParameters, $employeeIds);

			if (!empty($guestIds))
			{
				$comments[] = $this->getExpiredComment('COMMENT_POSTER_COMMENT_TASK_EXPIRED_SHORT', $liveParameters, $guestIds);
			}
		}
		elseif (!empty($guestIds))
		{
			$comments[] = $this->getExpiredComment('COMMENT_POSTER_COMMENT_TASK_EXPIRED_WITHOUT_EFFICIENCY', $liveParameters, $guestIds);
		}

		return $comments;
	}

	private function getExpiredComment(string $messageKey, array $liveParameters, array $userIds = []): Comment
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
			Comment::TYPE_EXPIRED,
			[[$messageKey, array_merge($replace, $liveParameters)]],
		);
	}
}
