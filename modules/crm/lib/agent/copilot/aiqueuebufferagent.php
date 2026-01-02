<?php

namespace Bitrix\Crm\Agent\Copilot;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Copilot\AiQueueBuffer\Consumer;

final class AiQueueBufferAgent extends AgentBase
{
	public const CONTINUE = true;

	public static function doRun(): bool
	{
		(Consumer::getInstance())->execute();

		$instance = new self();
		$instance->setExecutionPeriod($instance->getPeriodInSeconds());

		return self::CONTINUE;
	}

	private function getPeriodInSeconds(): int
	{
		return \COption::GetOptionInt('crm', 'ai_queue_buffer_agent_period', 60 * 5);
	}
}
