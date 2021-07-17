<?php

namespace Bitrix\Tasks\Internals\Counter\Queue;

use Bitrix\Tasks\Internals\Counter\CounterController;
use Bitrix\Tasks\Internals\Counter\CounterService;
use Bitrix\Tasks\Internals\Counter\Event\EventDictionary;

class Agent
{
	private static $processing = false;

	/**
	 * @return string
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function execute()
	{
		if (self::$processing)
		{
			return self::getAgentName();
		}

		self::$processing = true;

		$queue = Queue::getInstance();
		$rows = $queue->get(CounterController::STEP_LIMIT);

		if (empty($rows))
		{
			self::$processing = false;
			return '';
		}

		foreach ($rows as $row)
		{
			$userId = (int) $row['USER_ID'];
			(new CounterController($userId))->recount($row['TYPE'], $row['TASKS']);
		}

		$queue->done();

		self::$processing = false;

		return self::getAgentName();
	}

	public function __construct()
	{

	}

	/**
	 *
	 */
	public function addAgent(): void
	{
		$res = \CAgent::GetList(
			['ID' => 'DESC'],
			[
				'=NAME' => self::getAgentName()
			]
		);
		if ($res->Fetch())
		{
			return;
		}

		\CAgent::AddAgent(self::getAgentName(), "tasks", "N", 0, "", "Y", "");
	}

	/**
	 *
	 */
	public function removeAgent(): void
	{
		\CAgent::RemoveAgent(self::getAgentName(), 'tasks');
	}

	/**
	 * @return string
	 */
	private static function getAgentName(): string
	{
		return static::class . "::execute();";
	}
}