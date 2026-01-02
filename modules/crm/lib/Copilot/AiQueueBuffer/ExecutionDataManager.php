<?php

namespace Bitrix\Crm\Copilot\AiQueueBuffer;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Web\Json;
use COption;

/**
 * @internal
 */
final class ExecutionDataManager
{
	use Singleton;

	public function getExecutionData(): array
	{
		$dataString = COption::GetOptionString(
			'crm',
			'ai_queue_buffer_execution_data',
			null
		);

		return ($dataString ? Json::decode($dataString) : []);
	}

	public function setExecutionData(array $executionData): void
	{
		COption::SetOptionString(
			'crm',
			'ai_queue_buffer_execution_data',
			Json::encode($executionData)
		);
	}
}
