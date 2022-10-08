<?php

namespace Bitrix\Tasks\Access\Rule\Traits;

use Bitrix\Main\Loader;

trait GroupTrait
{
	/**
	 * @param int $userId
	 * @param int $groupId
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function canSetGroup(int $userId, int $groupId): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		if (
			!\Bitrix\Socialnetwork\Internals\Registry\FeaturePermRegistry::getInstance()->get(
				$groupId,
				'tasks',
				'edit_tasks',
				$userId
			)
			&&
			!\Bitrix\Socialnetwork\Internals\Registry\FeaturePermRegistry::getInstance()->get(
				$groupId,
				'tasks',
				'create_tasks',
				$userId
			)
		)
		{
			return false;
		}
		return true;
	}
}