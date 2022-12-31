<?php

namespace Bitrix\Tasks\FileUploader;

use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Util\User;
use Bitrix\UI\FileUploader\Configuration;
use Bitrix\UI\FileUploader\FileOwnershipCollection;
use Bitrix\UI\FileUploader\UploaderController;

class TaskController extends UploaderController
{
	/**
	 * @param array{
	 *     entityId: ?int,
	 * } $options
	 */
	public function __construct(array $options)
	{
		$options['taskId'] = ($options['taskId'] ? (int)$options['taskId'] : 0);
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
		['taskId' => $taskId] = $this->getOptions();

		if (!$taskId)
		{
			return true;
		}

		return TaskAccessController::can(User::getId(), ActionDictionary::ACTION_TASK_EDIT, $taskId);
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
