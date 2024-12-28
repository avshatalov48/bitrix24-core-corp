<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Provider;

use Bitrix\Tasks\Flow\Internal\FlowCopilotCollectedDataTable;
use Bitrix\Tasks\Internals\Log\Logger;
use Throwable;

class CollectedDataProvider
{
	public function get(int $flowId): CollectedData
	{
		try
		{
			$object = FlowCopilotCollectedDataTable::getById($flowId)->fetchObject();
			$data = $object?->getData() ?? [];

			return new CollectedData($flowId, $data);
		}
		catch (Throwable $t)
		{
			Logger::logThrowable($t);

			return new CollectedData($flowId);
		}
	}
}
