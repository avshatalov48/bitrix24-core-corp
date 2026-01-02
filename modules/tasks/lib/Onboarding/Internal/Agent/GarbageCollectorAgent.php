<?php

declare(strict_types=1);


namespace Bitrix\Tasks\Onboarding\Internal\Agent;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\Onboarding\Internal\Model\QueueTable;
use Bitrix\Tasks\Update\AgentInterface;
use Bitrix\Tasks\Update\AgentTrait;
use CAgent;

final class GarbageCollectorAgent implements AgentInterface
{
	use AgentTrait;

	private const INTERVAL = 259200; // 3 days
	private const DAYS_STUCK = 3;

	private static bool $isProcess = false;

	public static function install(): void
	{
		$timestamp = time() + 600; // 10 minutes offset
		$nextExec = DateTime::createFromTimestamp($timestamp)->toString();

		CAgent::AddAgent(
			name: self::getAgentName(),
			module: 'tasks',
			period: 'Y',
			interval: self::INTERVAL, // 5 minutes
			next_exec: $nextExec
		);
	}

	public static function execute(): string
	{
		if (self::$isProcess)
		{
			return '';
		}

		self::$isProcess = true;

		$agent = new self();
		$result = $agent->run();

		self::$isProcess = false;

		return $result;
	}

	private function run(): string
	{
		$from = (new DateTime())->add('-' . self::DAYS_STUCK . ' days');

		try
		{
			QueueTable::deleteByFilter([
				'<PROCESSED_DATE', $from,
				'IS_PROCESSED' => true,
			]);
		}
		catch (\Throwable $t)
		{
			Logger::handle($t, 'TASKS_ONBOARDING_GARBAGE_COLLECTOR');
		}

		return self::getAgentName();
	}
}