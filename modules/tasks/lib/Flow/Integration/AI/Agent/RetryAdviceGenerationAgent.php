<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Agent;

use Bitrix\Tasks\Flow\Integration\AI\Copilot\RequestSender;
use Bitrix\Tasks\Flow\Integration\AI\Provider\CollectedDataProvider;
use Bitrix\Tasks\Flow\Integration\AI\Provider\CollectedDataStatus;
use Bitrix\Tasks\Update\AgentInterface;
use CAgent;

class RetryAdviceGenerationAgent implements AgentInterface
{
	private const MODULE_ID = 'tasks';
	private const SIX_HOURS = 21600;

	public static function addAgent(int $flowId): void
	{
		$isAgentExists = is_array(
			\CAgent::GetList(
				[],
				['MODULE_ID' => self::MODULE_ID, 'NAME' => self::getAgentName($flowId)],
			)->Fetch()
		);
		if ($isAgentExists)
		{
			return;
		}

		\CAgent::AddAgent(
			name: self::getAgentName($flowId),
			module: self::MODULE_ID,
			interval: 5,
			next_exec: ConvertTimeStamp(time() + self::SIX_HOURS, "FULL"),
		);
	}

	public static function getAgentName(int $flowId, bool $withSlash = true): string
	{
		$prefix = $withSlash ? '\\' : '';
		return $prefix . static::class . "::execute($flowId);";
	}

	public static function removeAgent($flowId, string $moduleId = 'tasks'): void
	{
		CAgent::RemoveAgent(static::getAgentName($flowId), $moduleId);
		// backward compatibility
		CAgent::RemoveAgent(static::getAgentName($flowId, false), $moduleId);
	}

	public static function execute(int $flowId = 0): string
	{
		if ($flowId <= 0)
		{
			return '';
		}

		self::removeAgent($flowId, self::MODULE_ID);

		return (new self())->run($flowId);
	}

	private function run(int $flowId): string
	{
		$status = (new CollectedDataProvider())->getStatus($flowId);
		if ($status !== CollectedDataStatus::ERROR)
		{
			return '';
		}

		$sender = new RequestSender();
		$sender->sendRequest($flowId);

		return '';
	}
}