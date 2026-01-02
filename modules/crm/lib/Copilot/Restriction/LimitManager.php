<?php

namespace Bitrix\Crm\Copilot\Restriction;

use Bitrix\Crm\Traits\Singleton;
use COption;

final class LimitManager
{
	use Singleton;

	private const MAX_AI_QUIRES_LIMIT_PER_MONTH = 1000;

	public function isPeriodLimitExceeded(): bool
	{
		return $this->getExecutionCount() >= $this->getMaxCountLimit();
	}

	private function getExecutionCount(): int
	{
		return ExecutionDataManager::getInstance()->getExecutionData()['count'] ?? 0;
	}

	private function getMaxCountLimit(): int
	{
		return COption::GetOptionInt(
			'crm',
			'ai_queue_buffer_max_queue_limit_per_month',
			self::MAX_AI_QUIRES_LIMIT_PER_MONTH
		);
	}
}
