<?php

namespace Bitrix\Tasks\Flow\Grid\Preload;

use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\Internals\Registry\GroupRegistry;
use Exception;

class ProjectPreloader
{
	private static array $storage = [];

	final public function preload(int $userId, int ...$projectIds): void
	{
		try
		{
			GroupRegistry::getInstance()->load($projectIds);
			foreach ($projectIds as $projectId)
			{
				$project = GroupRegistry::getInstance()->get($projectId);
				if ($projectId === 0 || null === $project)
				{
					static::$storage[$projectId]['name'] = Loc::getMessage('TASKS_FLOW_LIST_COLUMN_GROUP_NAME_HIDDEN');
					static::$storage[$projectId]['hidden'] = true;
					continue;
				}

				if (!$project['VISIBLE'] && !$this->isMember($userId, $projectId))
				{
					static::$storage[$projectId]['name'] = Loc::getMessage('TASKS_FLOW_LIST_COLUMN_GROUP_NAME_HIDDEN');
					static::$storage[$projectId]['hidden'] = true;
					continue;
				}

				static::$storage[$projectId]['name'] = $project['NAME'];
				static::$storage[$projectId]['hidden'] = false;
			}
		}
		catch (Exception $e)
		{
			Logger::logThrowable($e);
			return;
		}
	}

	final public function get(int $projectId): array
	{
		return static::$storage[$projectId] ?? [];
	}

	/**
	 * @throws LoaderException
	 */
	private function isMember(int $userId, int $projectId): bool
	{
		return Group::isUserMember($projectId, $userId);
	}
}