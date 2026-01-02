<?php

declare(strict_types=1);


namespace Bitrix\Tasks\Onboarding\Comment;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Comments\Internals\Comment;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Onboarding\Comment\Trait\MessageTrait;
use Bitrix\Tasks\Onboarding\Comment\Trait\UserTrait;

class InvitedResponsibleNotViewTaskTwoDaysComment extends Comment
{
	use UserTrait;
	use MessageTrait;

	public function __construct(TaskObject $task)
	{
		$creatorId = $task->getCreatedBy();
		$responsibleId = $task->getResponsibleId();

		$replace = [
			'#CREATED_BY#' => $this->getBBCode($creatorId),
			'#RESPONSIBLE_ID#' => $this->getBBCode($responsibleId),
		];

		$messageKey = 'COMMENT_POSTER_ONBOARDING_COMMENT_INVITED_RESPONSIBLE_NOT_VIEW_TASK_TWO_DAYS_V2';

		$this->loadPosterMessages();

		parent::__construct(
			Loc::getMessage($messageKey, $replace),
			$creatorId,
			Comment::TYPE_ONBOARDING_COMMENT,
			[[$messageKey, array_merge($replace, [])]],
		);
	}
}