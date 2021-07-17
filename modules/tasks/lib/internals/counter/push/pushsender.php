<?php


namespace Bitrix\Tasks\Internals\Counter\Push;


use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Counter\Role;

class PushSender
{
	public const COMMAND_USER = 'user_counter';
	public const COMMAND_PROJECT = 'project_counter';

	private const MODULE_NAME = 'tasks';

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

		$types = [
			Role::ALL,
			Role::RESPONSIBLE,
			Role::ORIGINATOR,
			Role::ACCOMPLICE,
			Role::AUDITOR,
		];

		foreach ($userIds as $userId)
		{
			$pushData = [];
			$pushData['userId'] = $userId;

			$counter = Counter::getInstance($userId);
			$counters = $counter->getRawCounters();

			$pushData[Counter\CounterDictionary::COUNTER_TOTAL] = $counter->get(Counter\CounterDictionary::COUNTER_TOTAL);
			$pushData[Counter\CounterDictionary::COUNTER_PROJECTS_MAJOR] = $counter->get(Counter\CounterDictionary::COUNTER_SONET_TOTAL_EXPIRED)
				+ $counter->get(Counter\CounterDictionary::COUNTER_SONET_TOTAL_COMMENTS);

			/**
			 * for menu's counters group 0 is a total counters (tasks with any groups or without groups)
			 */
			$groupIds = [0];
			foreach ($counters as $type => $data)
			{
				$groupIds = array_merge($groupIds, array_keys($data));
			}
			$groupIds = array_unique($groupIds);

			foreach ($groupIds as $groupId)
			{
				foreach ($types as $type)
				{
					$data = $counter->getCounters($type, $groupId, ['SKIP_ACCESS_CHECK' => true]);
					foreach ($data as $key => $value)
					{
						$pushData[$groupId][$type][$key] = $value['counter'];
					}
				}
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
			'module_id' => self::MODULE_NAME,
			'command' => $command,
			'params' => $params
		]);
	}
}