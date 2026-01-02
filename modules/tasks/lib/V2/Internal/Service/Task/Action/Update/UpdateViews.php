<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Tasks\V2\Internal\Service\Task\Trait\ParticipantTrait;
use Bitrix\Tasks\Internals\Task\ViewedTable;

class UpdateViews
{
	use ParticipantTrait;

	public function __invoke(array $fullTaskData, array $sourceTaskData): void
	{
		$taskId = (int)$fullTaskData['ID'];

		$newParticipants = $this->getParticipants($fullTaskData);
		$oldParticipants = $this->getParticipants($sourceTaskData);
		$addedParticipants = array_unique(array_diff($newParticipants, $oldParticipants));

		if (empty($addedParticipants))
		{
			return;
		}

		$viewedDate = \Bitrix\Tasks\Comments\Task::getLastCommentTime($taskId);

		if (!$viewedDate)
		{
			return;
		}

		foreach ($addedParticipants as $userId)
		{
			ViewedTable::set($taskId, $userId, $viewedDate);
		}
	}
}