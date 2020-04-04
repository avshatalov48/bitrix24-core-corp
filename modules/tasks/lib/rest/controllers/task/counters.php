<?php
namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Rest\Controllers\Base;
use Bitrix\Tasks\Internals;

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
	public function getAction($userId=0, $groupId = 0, $type = 'view_all')
	{
	    if(!$this->checkGroupReadAccess($groupId))
        {
            throw new \TasksException('Access denied');
        }

		if(!$userId)
		{
			$userId = $this->getCurrentUser()->getId();
		}

		$counterInstance = Internals\Counter::getInstance($userId, $groupId);

		return $counterInstance->getCounters($type);
	}

	private function checkGroupReadAccess($groupId)
    {
        if ($groupId > 0)
        {
            // can we see all tasks in this group?
            $featurePerms = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
                SONET_ENTITY_GROUP,
                array($groupId),
                'tasks',
                'view_all'
            );

            $canViewGroup = is_array($featurePerms) &&
                isset($featurePerms[$groupId]) &&
                $featurePerms[$groupId];

            if (!$canViewGroup)
            {
                // okay, can we see at least our own tasks in this group?
                $featurePerms = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
                    SONET_ENTITY_GROUP,
                    array($groupId),
                    'tasks',
                    'view'
                );
                $canViewGroup = is_array($featurePerms) &&
                    isset($featurePerms[$groupId]) &&
                    $featurePerms[$groupId];
            }

            if (!$canViewGroup)
            {
                return false;
            }
        }

        return true;
    }
}