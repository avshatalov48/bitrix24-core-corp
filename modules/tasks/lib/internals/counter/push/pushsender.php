<?php


namespace Bitrix\Tasks\Internals\Counter\Push;


use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Counter\Role;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;

class PushSender
{
	public const COMMAND_USER = 'user_counter';
	public const COMMAND_PROJECT = 'project_counter';

	/**
	 * @param array $users
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function sendUserCounters(array $userIds): void
	{
		if (!ModuleManager::isModuleInstalled('pull') || !Loader::includeModule('pull'))
		{
			return;
		}

		foreach ($userIds as $userId)
		{
			$pushData = [];
			$pushData['userId'] = $userId;

			$counter = Counter::getInstance($userId);
			$counters = $counter->getRawCounters();

			$pushData[CounterDictionary::COUNTER_TOTAL] = $counter->get(CounterDictionary::COUNTER_TOTAL);
			$pushData[CounterDictionary::COUNTER_PROJECTS_MAJOR] =
				$counter->get(CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS)
				+ $counter->get(CounterDictionary::COUNTER_PROJECTS_TOTAL_COMMENTS)
				+ $counter->get(CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED)
				+ $counter->get(CounterDictionary::COUNTER_PROJECTS_TOTAL_EXPIRED);

			$pushData[CounterDictionary::COUNTER_SCRUM_TOTAL_COMMENTS] = $counter->get(CounterDictionary::COUNTER_SCRUM_TOTAL_COMMENTS);

			/**
			 * for menu's counters group 0 is a total counters (tasks with any groups or without groups)
			 */
			$groupIds = [0];
			foreach ($counters as $data)
			{
				$groupIds = array_merge($groupIds, array_keys($data));
			}
			$groupIds = array_unique($groupIds);

			foreach ($groupIds as $groupId)
			{
				$pushData[$groupId][Role::ALL][CounterDictionary::COUNTER_TOTAL] = $counter->get(CounterDictionary::COUNTER_MEMBER_TOTAL, $groupId);
				$pushData[$groupId][Role::ALL][CounterDictionary::COUNTER_EXPIRED] = $counter->get(CounterDictionary::COUNTER_EXPIRED, $groupId);
				$pushData[$groupId][Role::ALL][CounterDictionary::COUNTER_NEW_COMMENTS] = $counter->get(CounterDictionary::COUNTER_NEW_COMMENTS, $groupId);
				$pushData[$groupId][Role::ALL][CounterDictionary::COUNTER_MUTED_NEW_COMMENTS] = $counter->get(CounterDictionary::COUNTER_MUTED_NEW_COMMENTS, $groupId);

				$pushData[$groupId][Role::RESPONSIBLE][CounterDictionary::COUNTER_TOTAL] = $counter->get(CounterDictionary::COUNTER_MY, $groupId);
				$pushData[$groupId][Role::RESPONSIBLE][CounterDictionary::COUNTER_EXPIRED] = $counter->get(CounterDictionary::COUNTER_MY_EXPIRED, $groupId);
				$pushData[$groupId][Role::RESPONSIBLE][CounterDictionary::COUNTER_NEW_COMMENTS] = $counter->get(CounterDictionary::COUNTER_MY_NEW_COMMENTS, $groupId);
				$pushData[$groupId][Role::RESPONSIBLE][CounterDictionary::COUNTER_MUTED_NEW_COMMENTS] = $counter->get(CounterDictionary::COUNTER_MY_MUTED_NEW_COMMENTS, $groupId);

				$pushData[$groupId][Role::ORIGINATOR][CounterDictionary::COUNTER_TOTAL] = $counter->get(CounterDictionary::COUNTER_ORIGINATOR, $groupId);
				$pushData[$groupId][Role::ORIGINATOR][CounterDictionary::COUNTER_EXPIRED] = $counter->get(CounterDictionary::COUNTER_ORIGINATOR_EXPIRED, $groupId);
				$pushData[$groupId][Role::ORIGINATOR][CounterDictionary::COUNTER_NEW_COMMENTS] = $counter->get(CounterDictionary::COUNTER_ORIGINATOR_NEW_COMMENTS, $groupId);
				$pushData[$groupId][Role::ORIGINATOR][CounterDictionary::COUNTER_MUTED_NEW_COMMENTS] = $counter->get(CounterDictionary::COUNTER_ORIGINATOR_MUTED_NEW_COMMENTS, $groupId);

				$pushData[$groupId][Role::ACCOMPLICE][CounterDictionary::COUNTER_TOTAL] = $counter->get(CounterDictionary::COUNTER_ACCOMPLICES, $groupId);
				$pushData[$groupId][Role::ACCOMPLICE][CounterDictionary::COUNTER_EXPIRED] = $counter->get(CounterDictionary::COUNTER_ACCOMPLICES_EXPIRED, $groupId);
				$pushData[$groupId][Role::ACCOMPLICE][CounterDictionary::COUNTER_NEW_COMMENTS] = $counter->get(CounterDictionary::COUNTER_ACCOMPLICES_NEW_COMMENTS, $groupId);
				$pushData[$groupId][Role::ACCOMPLICE][CounterDictionary::COUNTER_MUTED_NEW_COMMENTS] = $counter->get(CounterDictionary::COUNTER_ACCOMPLICES_MUTED_NEW_COMMENTS, $groupId);

				$pushData[$groupId][Role::AUDITOR][CounterDictionary::COUNTER_TOTAL] = $counter->get(CounterDictionary::COUNTER_AUDITOR, $groupId);
				$pushData[$groupId][Role::AUDITOR][CounterDictionary::COUNTER_EXPIRED] = $counter->get(CounterDictionary::COUNTER_AUDITOR_EXPIRED, $groupId);
				$pushData[$groupId][Role::AUDITOR][CounterDictionary::COUNTER_NEW_COMMENTS] = $counter->get(CounterDictionary::COUNTER_AUDITOR_NEW_COMMENTS, $groupId);
				$pushData[$groupId][Role::AUDITOR][CounterDictionary::COUNTER_MUTED_NEW_COMMENTS] = $counter->get(CounterDictionary::COUNTER_AUDITOR_MUTED_NEW_COMMENTS, $groupId);
			}

			$this->createPush([$userId], self::COMMAND_USER, $pushData);
		}
	}

	/**
	 * @param array $userIds
	 * @param string $command
	 * @param array $params
	 */
	public function createPush(array $userIds, string $command, array $params)
	{
		if (!ModuleManager::isModuleInstalled('pull') || !Loader::includeModule('pull'))
		{
			return;
		}

		PushService::addEvent($userIds, [
			'module_id' => PushService::MODULE_NAME,
			'command' => $command,
			'params' => $params
		]);
	}
}