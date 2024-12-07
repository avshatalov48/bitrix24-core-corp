<?php

namespace Bitrix\Tasks\FileUploader;

use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;
use Bitrix\Tasks\Util\User;
use Bitrix\UI\FileUploader\Configuration;
use Bitrix\UI\FileUploader\FileOwnershipCollection;
use Bitrix\UI\FileUploader\UploaderController;

class TaskResultController extends UploaderController
{
	public function __construct(array $options)
	{
		$options['taskId'] = ($options['taskId'] ? (int)$options['taskId'] : 0);
		$options['commentId'] = ($options['commentId'] ? (int)$options['commentId'] : 0);

		parent::__construct($options);
	}

	public function isAvailable(): bool
	{
		['taskId' => $taskId] = $this->getOptions();

		if (!$taskId)
		{
			return true;
		}

		return TaskAccessController::can(User::getId(), ActionDictionary::ACTION_TASK_READ, $taskId);
	}

	public function getConfiguration(): Configuration
	{
		return new Configuration();
	}

	public function canUpload(): bool
	{
		['commentId' => $commentId] = $this->getOptions();

		if (
			!$commentId
			|| !($result = ResultTable::getByCommentId($commentId))
		)
		{
			return true;
		}

		return ($result->getCreatedBy() === User::getId());
	}

	public function verifyFileOwner(FileOwnershipCollection $files): void
	{
	}

	public function canView(): bool
	{
		return false;
	}

	public function canRemove(): bool
	{
		return false;
	}
}
