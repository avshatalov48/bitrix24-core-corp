<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Provider;

use Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice;
use Bitrix\Tasks\Flow\Internal\EO_FlowCopilotAdvice_Collection;
use Bitrix\Tasks\Flow\Internal\FlowCopilotAdviceTable;
use Bitrix\Tasks\Internals\Log\Logger;
use Throwable;

class AdviceProvider
{
	public function get(int $flowId): ?FlowCopilotAdvice
	{
		try
		{
			return FlowCopilotAdviceTable::getByPrimary($flowId)->fetchObject();
		}
		catch (Throwable $t)
		{
			Logger::logThrowable($t);

			return null;
		}
	}

	public function getList(array $flowIds): ?EO_FlowCopilotAdvice_Collection
	{
		try
		{
			return FlowCopilotAdviceTable::getList(['filter' => ['=FLOW_ID' => $flowIds]])->fetchCollection();
		}
		catch (Throwable $t)
		{
			Logger::logThrowable($t);

			return null;
		}
	}
}
