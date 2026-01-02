<?php

declare(strict_types=1);


namespace Bitrix\Tasks\Onboarding\Comment;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Comments\Internals\Comment;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Onboarding\Comment\Trait\MessageTrait;
use Bitrix\Tasks\Onboarding\Comment\Trait\UserTrait;

class ResponsibleInvitationNotAcceptedOneDayComment extends Comment
{
	use MessageTrait;
	use UserTrait;

	public function __construct(TaskObject $task)
	{
		$creatorId = $task->getCreatedBy();

		$replace = [
			'#CREATED_BY#' => $this->getBBCode($creatorId),
		];

		$messageKey = 'COMMENT_POSTER_ONBOARDING_COMMENT_RESPONSIBLE_INVITATION_NOT_ACCEPTED_ONE_DAY_V2';

		$this->loadPosterMessages();

		parent::__construct(
			Loc::getMessage($messageKey, $replace),
			$creatorId,
			Comment::TYPE_ONBOARDING_COMMENT,
			[[$messageKey, array_merge($replace, [])]],
		);
	}
}