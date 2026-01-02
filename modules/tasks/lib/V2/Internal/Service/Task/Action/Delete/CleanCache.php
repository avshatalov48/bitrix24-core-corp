<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete;

use Bitrix\Main\Data\Cache;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\ParticipantTrait;
use Bitrix\Tasks\Internals\CacheConfig;
use Bitrix\Tasks\Member\Service\TaskMemberService;
use CCacheManager;

class CleanCache
{
	use ParticipantTrait;

	public function __invoke(array $fullTaskData): void
	{
		TaskAccessController::dropItemCache($fullTaskData['ID']);
		TaskMemberService::invalidate();

		$participants = $this->getParticipants($fullTaskData);

		$cacheManager = $this->getCacheManager();

		$cacheManager->ClearByTag("tasks_" . $fullTaskData['ID']);

		if ($fullTaskData["GROUP_ID"])
		{
			$cacheManager->ClearByTag("tasks_group_" . $fullTaskData["GROUP_ID"]);
		}
		foreach ($participants as $userId)
		{
			$cacheManager->ClearByTag("tasks_user_" . $userId);
		}

		$cache = Cache::createInstance();
		$cache->clean(CacheConfig::UNIQUE_CODE, CacheConfig::DIRECTORY);
	}

	private function getCacheManager(): CCacheManager
	{
		global $CACHE_MANAGER;

		return $CACHE_MANAGER;
	}
}