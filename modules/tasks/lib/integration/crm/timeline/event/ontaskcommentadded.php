<?php

namespace Bitrix\Tasks\Integration\CRM\Timeline\Event;

use Bitrix\Disk\Uf\ForumMessageConnector;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Integration\CRM\Timeline\EventTrait;
use Bitrix\Tasks\Internals\TaskObject;

class OnTaskCommentAdded implements TimeLineEvent
{
	use EventTrait;

	private ?TaskObject $task;
	private int $userId;
	private int $commentId;
	private int $fromUser;
	private DateTime $lastCommentDate;

	public function __construct(
		?TaskObject $task,
		int $userId,
		int $commentId,
		DateTime $commentDate,
		int $fromUser
	)
	{
		$this->task = $task;
		$this->userId = $userId;
		$this->commentId = $commentId;
		$this->lastCommentDate = $commentDate;
		$this->fromUser = $fromUser;
	}

	public function getPayload(): array
	{
		return [
			'AUTHOR_ID' => $this->userId,
			'TASK_FILE_IDS' => $this->getFiles($this->commentId, ForumMessageConnector::class),
			'LAST_COMMENT_DATE' => $this->lastCommentDate->getTimestamp(),
			'TASK_ID' => $this->task?->getId(),
			'FROM_USER' => $this->fromUser,
			'REFRESH_TASK_ACTIVITY' => true,
			'RUN_IN_BACKGROUND' => true
		];
	}

	public function getEndpoint(): string
	{
		return 'onTaskCommentAdded';
	}
}