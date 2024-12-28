<?php

namespace Bitrix\Tasks\Flow\Notification;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Flow\Notification\Command\SendPingCommand;
use Bitrix\Tasks\Flow\Notification\Command\SendPingCommandHandler;
use Bitrix\Tasks\Flow\Notification\Config\When;
use Bitrix\Tasks\Internals\TaskObject;

class PingAgent
{
	private static bool $processing = false;

	public static function execute($taskId, $flowId, $offset)
	{
		$taskId = (int)$taskId;
		$flowId = (int)$flowId;
		$offset = (int)$offset;

		if (self::$processing)
		{
			return self::getAgentName($taskId, $flowId, $offset);
		}

		self::$processing = true;

		$command = new SendPingCommand($taskId, $flowId, $offset);
		(new SendPingCommandHandler())($command);

		self::$processing = false;

		return '';
	}

	public function __construct()
	{

	}

	public function removeAgents(int $taskId): void
	{
		$res = \CAgent::GetList(
			['ID' => 'DESC'],
			[
				'=MODULE' => 'tasks',
				'NAME' => static::class . "::execute(". $taskId .", %",
			]
		);
		while ($row = $res->Fetch())
		{
			\CAgent::Delete($row['ID']);
		}
	}

	public function addAgent(TaskObject $task, int $flowId, Config\Item $item): void
	{
		if (!in_array($item->getWhen()->getType(), [When::BEFORE_EXPIRE, When::BEFORE_EXPIRE_HALF_TIME]))
		{
			return;
		}

		if (!$task->getDeadline())
		{
			return;
		}

		$now = new DateTime();
		if ($now->getTimestamp() >= $task->getDeadline()->getTimestamp())
		{
			return;
		}

		$offset = 0;
		$deadLine = clone $task->getDeadline();

		switch ($item->getWhen()->getType())
		{
			case When::BEFORE_EXPIRE_HALF_TIME:
				$diff = (int)(($deadLine->getTimestamp() - $now->getTimestamp()) / 2);
				$deadLine->add("-$diff seconds");
				break;
			case When::BEFORE_EXPIRE:
				$offset = $item->getWhen()->getValue()['offset'];
				$deadLine->add("-$offset minutes");
				break;
		}

		\CAgent::AddAgent(
			self::getAgentName($task->getId(), $flowId, $offset),
			'tasks',
			'N',
			0,
			'',
			'Y',
			$deadLine,
		);
	}

	private static function getAgentName(int $taskId, int $flowId, int $offset): string
	{
		return static::class . "::execute(". $taskId .", " . $flowId . ", " . $offset . ");";
	}
}