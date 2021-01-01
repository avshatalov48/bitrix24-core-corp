<?php


namespace Bitrix\Tasks\Internals\Counter;


use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Internals\Counter;

class PushSender
{
	/**
	 * @param array $users
	 */
	public static function send(array $users): void
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

		foreach ($users as $userId)
		{
			$pushData = [];
			$pushData['userId'] = $userId;

			$counter = Counter::getInstance($userId);
			$counters = $counter->getRawCounters();

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

			PushService::addEvent([$userId], [
				'module_id' => 'tasks',
				'command' => 'user_counter',
				'params' => $pushData,
			]);
		}
	}
}