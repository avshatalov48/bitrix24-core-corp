<?php

namespace Bitrix\Tasks\Internals\Counter\Queue;

use Bitrix\Tasks\Internals\Counter\CounterController;
use Bitrix\Tasks\Internals\Counter\CounterService;
use Bitrix\Tasks\Internals\Counter\Event\EventDictionary;
use Bitrix\Tasks\Update\AgentInterface;
use Bitrix\Tasks\Update\AgentTrait;
use CAgent;

class Agent implements AgentInterface
{
	use AgentTrait;

	private static $processing = false;

	/**
	 * @return string
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function execute(): string
	{
		if (self::$processing)
		{
			return static::getAgentName();
		}

		self::$processing = true;

		$queue = Queue::getInstance();
		$rows = $queue->get(CounterController::getStepLimit());

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

		return static::getAgentName();
	}

	public function __construct()
	{

	}

	public function addAgent(): void
	{
		$res = \CAgent::GetList(
			['ID' => 'DESC'],
			[
				'=NAME' => static::getAgentName()
			]
		);
		if ($res->Fetch())
		{
			return;
		}

		CAgent::AddAgent(static::getAgentName(), "tasks", "N", 0);
	}
}