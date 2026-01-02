<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Disk\Service\Task;

use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Main\Loader;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\UF\UserField;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Integration\Disk\Entity\DiskFile;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;
use Bitrix\Tasks\V2\Internal\Service\Task\UpdateService;
use CUserTypeManager;

class AttachmentService
{
	public function __construct(
		private readonly UpdateService $updateService,
	)
	{

	}

	public function add(int $taskId, int $userId, array $fileIds): void
	{
		if (empty($fileIds))
		{
			return;
		}

		$ufManager = $this->getUfManager();

		$current = $ufManager->GetUserFields(UserField::TASK, $taskId)[UserField::TASK_ATTACHMENTS]['VALUE'] ?? [];
		$current = is_array($current) ? $current : [];

		$new = array_unique(array_merge($current, $fileIds));
		$new = array_values($new);

		$task = new Task(
			id: $taskId,
			fileIds: $new,
			changedTs: time(),
			changedBy: new User(id: $userId),
		);

		$config = new UpdateConfig($userId);

		// update changelog
		$this->updateService->update(
			task: $task,
			config: $config,
		);

		$fields = [UserField::TASK_ATTACHMENTS => $new];

		$ufManager->Update(UserField::TASK, $taskId, $fields, $userId);
	}

	public function delete(int $taskId, int $userId, array $fileIds): void
	{
		if (!Loader::includeModule('disk'))
		{
			return;
		}

		if (empty($fileIds))
		{
			return;
		}

		$ufManager = $this->getUfManager();

		$current = $ufManager->GetUserFields(UserField::TASK, $taskId)[UserField::TASK_ATTACHMENTS]['VALUE'] ?? [];
		$current = is_array($current) ? $current : [];

		if (empty($current))
		{
			return;
		}

		$fileIdsToRemove = [];
		$currentAttachments = Container::getInstance()->getDiskFileRepository()->getByIds($current);

		foreach ($fileIds as $fileId)
		{
			[$type, $realValue] = FileUserType::detectType($fileId);
			if ($type === FileUserType::TYPE_NEW_OBJECT)
			{
				/** @var DiskFile $attachment */
				foreach ($currentAttachments as $attachment)
				{
					if ($attachment->customData['objectId'] === (int)$realValue)
					{
						$fileIdsToRemove[] = $attachment->id;

						break;
					}
				}
			}
			else
			{
				$fileIdsToRemove[] = $fileId;
			}
		}

		$new = array_diff($current, $fileIdsToRemove);
		$new = array_values($new);

		$task = new Task(
			id: $taskId,
			fileIds: $new,
			changedTs: time(),
			changedBy: new User(id: $userId),
		);

		$config = new UpdateConfig($userId);

		// update changelog
		$this->updateService->update(
			task: $task,
			config: $config,
		);

		// if no files left, we need to set empty value
		if (empty($new))
		{
			$new[] = '';
		}

		$fields = [UserField::TASK_ATTACHMENTS => $new];

		$ufManager->Update(UserField::TASK, $taskId, $fields, $userId);
	}

	protected function getUfManager(): CUserTypeManager
	{
		global $USER_FIELD_MANAGER;

		return $USER_FIELD_MANAGER;
	}
}
