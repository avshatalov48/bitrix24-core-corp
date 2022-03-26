<?php
namespace Bitrix\Tasks\Rest\Controllers\Task;

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

		$counterInstance = Counter::getInstance($userId);

		return $counterInstance->getCounters($type, (int) $groupId);
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

	public function getProjectsTotalCounterAction(int $userId = 0): int
	{
		$userId = ($userId ?: (int)$this->getCurrentUser()->getId());
		$counter = Counter::getInstance($userId);

		return
			$counter->get(Counter\CounterDictionary::COUNTER_SONET_TOTAL_EXPIRED)
			+ $counter->get(Counter\CounterDictionary::COUNTER_SONET_TOTAL_COMMENTS)
		;
	}
}