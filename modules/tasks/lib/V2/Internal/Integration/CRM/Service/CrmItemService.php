<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\CRM\Service;

use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\UF\UserField;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;
use Bitrix\Tasks\V2\Internal\Service\Task\UpdateService;
use CUserTypeManager;

class CrmItemService
{
	public function __construct(
		private readonly UpdateService $updateService,
	)
	{

	}

	public function set(int $taskId, int $userId, array $crmItemIds): void
	{
		$task = new Task(
			id: $taskId,
			changedTs: time(),
			changedBy: new User(id: $userId),
			crmItemIds: $crmItemIds,
		);

		$config = new UpdateConfig($userId);

		// update changelog
		$this->updateService->update(
			task: $task,
			config: $config,
		);

		$ufManager = $this->getUfManager();

		$fields = [UserField::TASK_CRM => $crmItemIds];

		$ufManager->Update(UserField::TASK, $taskId, $fields, $userId);
	}

	public function add(int $taskId, int $userId, array $crmItemIds): void
	{
		if (empty($crmItemIds))
		{
			return;
		}

		$ufManager = $this->getUfManager();

		$current = $ufManager->GetUserFields(UserField::TASK, $taskId)[UserField::TASK_CRM]['VALUE'] ?? [];
		$current = is_array($current) ? $current : [];

		$new = array_unique(array_merge($current, $crmItemIds));
		$new = array_values($new);

		$task = new Task(
			id: $taskId,
			changedTs: time(),
			changedBy: new User(id: $userId),
			crmItemIds: $new,
		);

		$config = new UpdateConfig($userId);

		// update changelog
		$this->updateService->update(
			task: $task,
			config: $config,
		);

		$fields = [UserField::TASK_CRM => $new];

		$ufManager->Update(UserField::TASK, $taskId, $fields, $userId);
	}

	public function delete(int $taskId, int $userId, array $crmItemIds): void
	{
		if (empty($crmItemIds))
		{
			return;
		}

		$ufManager = $this->getUfManager();

		$current = $ufManager->GetUserFields(UserField::TASK, $taskId)[UserField::TASK_CRM]['VALUE'] ?? [];
		$current = is_array($current) ? $current : [];

		$new = array_diff($current, $crmItemIds);
		$new = array_values($new);

		$task = new Task(
			id: $taskId,
			changedTs: time(),
			changedBy: new User(id: $userId),
			crmItemIds: $new,
		);

		$config = new UpdateConfig($userId);

		// update changelog
		$this->updateService->update(
			task: $task,
			config: $config,
		);

		$fields = [UserField::TASK_CRM => $new];

		$ufManager->Update(UserField::TASK, $taskId, $fields, $userId);
	}

	private function getUfManager(): CUserTypeManager
	{
		global $USER_FIELD_MANAGER;

		return $USER_FIELD_MANAGER;
	}
}
