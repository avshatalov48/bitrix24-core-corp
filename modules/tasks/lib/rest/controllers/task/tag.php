<?php

namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Main\Error;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Rest\Controllers\Base;

/**
 * Class Tag
 * @package Bitrix\Tasks\Rest\Controllers\Task
 */
class Tag extends Base
{
	/**
	 * @param string $tag
	 * @param int $groupId
	 * @return array|null
	 */
	public function createAction(string $tag, int $groupId = 0): ?array
	{
		if ($groupId > 0)
		{
			return $this->createForGroup($tag, $groupId);
		}

		return $this->createForUser($tag);
	}

	private function createForGroup(string $tag, int $groupId): ?array
	{
		$userId = $this->getUserId();

		if (!TaskAccessController::can($userId, ActionDictionary::ACTION_TAG_CREATE, null, ['GROUP_ID' => $groupId]))
		{
			$this->addError(new Error('Insufficient permission to create tag in the group'));

			return null;
		}

		$tagService = new \Bitrix\Tasks\Control\Tag($userId);
		if (!$tagService->isExistsByGroup($groupId, $tag))
		{
			$tagService->addTagToGroup($tag, $groupId);
			$this->sendPush('tag_added', ['groupId' => $groupId]);
		}

		return ['id' => $tagService->getIdByGroup($groupId, $tag)];
	}

	private function createForUser(string $tag): ?array
	{
		$tagService = new \Bitrix\Tasks\Control\Tag($this->getUserId());
		if (!$tagService->isExistsByUser($tag))
		{
			$tagService->addTagToUser($tag);
			$this->sendPush('tag_added');
		}

		return ['id' => $tagService->getIdByUser(['NAME' => $tag])];
	}

	private function sendPush(string $command, array $params = []): void
	{
		PushService::addEvent(
			$this->getUserId(),
			[
				'module_id' => 'tasks',
				'command' => $command,
				'params' => $params,
			]
		);

	}
}