<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Stepper;

use Bitrix\Main\Update\Stepper;
use Bitrix\Tasks\Flow\Integration\AI\Copilot\RequestSender;
use Bitrix\Tasks\Flow\Integration\AI\Provider\CollectedDataProvider;
use Bitrix\Tasks\Flow\Integration\AI\Provider\CollectedDataStatus;
use Bitrix\Tasks\Internals\Log\Logger;
use Throwable;

class PromoRequestsCountUpdatedStepper extends Stepper
{
	protected static $moduleId = 'tasks';
	private const FLOWS_PER_HIT = 5;

	public function execute(array &$option): bool
	{
		try
		{
			$flowIds = $this->getFlowIds();

			$sender = new RequestSender();
			foreach ($flowIds as $flowId)
			{
				$sender->sendRequest($flowId);
			}

			if (count($flowIds) < self::FLOWS_PER_HIT)
			{
				return self::FINISH_EXECUTION;
			}

			return self::CONTINUE_EXECUTION;
		}
		catch (Throwable $t)
		{
			Logger::logThrowable($t);

			return self::FINISH_EXECUTION;
		}
	}

	private function getFlowIds(): array
	{
		$provider = new CollectedDataProvider();
		$flowCollection = $provider->getFlowIdsByStatus(CollectedDataStatus::LIMIT_EXCEEDED, self::FLOWS_PER_HIT);

		return $flowCollection?->getFlowIdList() ?? [];
	}
}
