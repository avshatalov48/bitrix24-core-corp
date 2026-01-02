<?php

namespace Bitrix\Crm\Copilot\Restriction;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Web\Json;
use COption;

/**
 * @internal
 */
final class ExecutionDataManager
{
	use Singleton;

	public function incrementExecutionCount(int $count = 1): void
	{
		if ($count <= 0)
		{
			return;
		}

		$executionData = $this->getExecutionData();
		$executionData['count'] = $count + ($executionData['count'] ?? 0);

		$this->setExecutionData($executionData);
	}

	public function clearExecutionData(): void
	{
		$executionData = $this->getExecutionData();
		$executionData['count'] = 0;

		$this->setExecutionData($executionData);
	}

	public function getExecutionData(): array
	{
		$dataString = COption::GetOptionString('crm', 'ai_queue_buffer_execution_data', null);

		if (!$dataString)
		{
			return [];
		}

		$decodedData = Json::decode($dataString);

		return is_array($decodedData) ? $decodedData : [];
	}

	private function setExecutionData(array $executionData): void
	{
		$data = Json::encode($executionData);

		COption::SetOptionString('crm', 'ai_queue_buffer_execution_data', $data);
	}
}
