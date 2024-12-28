<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Stepper;

use Bitrix\Main\Config\Option;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Tasks\Flow\Integration\AI\Control\CollectedDataService;
use Bitrix\Tasks\Flow\Integration\AI\Control\Command\DeleteCommand;

class Collector
{
	public static function execute(int $flowId, int $delay = 0): void
	{
		self::deleteCollectedData($flowId);

		Option::delete('main.stepper.tasks', ['name' => TaskCollector::class . "({$flowId})"]);

		// queue inside, it is just an entry point
		TaskCollector::bind($delay, [$flowId]);
	}

	private static function deleteCollectedData(int $flowId): void
	{
		$deleteCommand = (new DeleteCommand())->setFlowId($flowId);

		/** @var CollectedDataService $collectedDataService */
		$collectedDataService = ServiceLocator::getInstance()->get('tasks.flow.copilot.collected.data.service');
		$collectedDataService->delete($deleteCommand);
	}
}
