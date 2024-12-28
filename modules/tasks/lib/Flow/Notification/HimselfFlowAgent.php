<?php

namespace Bitrix\Tasks\Flow\Notification;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Flow\Notification\Command\NotifyHimselfFlowAdminCommand;
use Bitrix\Tasks\Flow\Notification\Command\NotifyHimselfFlowAdminCommandHandler;
use Bitrix\Tasks\Flow\Notification\Config\When;
use Bitrix\Tasks\Internals\TaskObject;

class HimselfFlowAgent
{
	private const ONE_DAY_TEXT = '1 day';
	private static bool $processing = false;

	public static function execute($taskId, $flowId)
	{
		$taskId = (int)$taskId;
		$flowId = (int)$flowId;

		if (self::$processing)
		{
			return self::getAgentName($taskId, $flowId);
		}

		self::$processing = true;

		$command = new NotifyHimselfFlowAdminCommand($taskId, $flowId);
		(new NotifyHimselfFlowAdminCommandHandler())($command);

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
		if ($item->getWhen()->getType() !== When::HIMSELF_FLOW_TASK_NOT_TAKEN)
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

		$dateExecute = (clone $now)->add("+ " . self::ONE_DAY_TEXT);


		\CAgent::AddAgent(
			self::getAgentName($task->getId(), $flowId),
			'tasks',
			'N',
			0,
			'',
			'Y',
			$dateExecute,
		);
	}

	private static function getAgentName(int $taskId, int $flowId): string
	{
		return static::class . "::execute(". $taskId .", " . $flowId . ");";
	}
}