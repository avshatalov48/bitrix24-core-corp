<?php

namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Rest\Controllers\Base;
use Bitrix\Tasks\Internals\Counter;

class Counters extends Base
{
	/**
	 * Get counter for user (current user if userId=0)
	 *
	 * @param int $userId
	 * @param int $groupId
	 * @param string $type
	 *
	 * @return array
	 * @throws \TasksException
	 */
	public function getAction($userId = 0, $groupId = 0, $type = 'view_all'): ?array
	{
		if (!$this->checkGroupReadAccess($groupId))
		{
			$this->addError(new Error('Group not found or access denied.'));
			return null;
		}

		if (!$userId)
		{
			$userId = $this->getCurrentUser()->getId();
		}

		$counterInstance = Counter::getInstance($userId);

		return $counterInstance->getCounters($type, (int)$groupId);
	}

	private function checkGroupReadAccess($groupId)
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}
		if ($groupId > 0)
		{
			// can we see all tasks in this group?
			$featurePerms = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
				SONET_ENTITY_GROUP,
				[$groupId],
				'tasks',
				'view_all'
			);

			$canViewGroup = is_array($featurePerms)
				&& isset($featurePerms[$groupId])
				&& $featurePerms[$groupId];

			if (!$canViewGroup)
			{
				// okay, can we see at least our own tasks in this group?
				$featurePerms = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
					SONET_ENTITY_GROUP,
					[$groupId],
					'tasks',
					'view'
				);
				$canViewGroup = is_array($featurePerms)
					&& isset($featurePerms[$groupId])
					&& $featurePerms[$groupId];
			}

			if (!$canViewGroup)
			{
				return false;
			}
		}

		return true;
	}
}