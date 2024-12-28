<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Event;

use Bitrix\Main\EventResult;
use Bitrix\Tasks\Flow\Integration\AI\Configuration;
use Bitrix\Tasks\Flow\Integration\AI\FlowCopilotFeature;
use Bitrix\Tasks\Flow\Integration\AI\Provider\AdviceProvider;
use Bitrix\Tasks\Flow\Integration\AI\Stepper\Collector;
use Bitrix\Tasks\Flow\Provider\TaskProvider;

class TaskEventListener
{
	public static function onTaskAdd(int $taskId, array $fields): EventResult
	{
		if (!FlowCopilotFeature::isOn() || !FlowCopilotFeature::isAdviceAutoGenerationOn())
		{
			return new EventResult(EventResult::SUCCESS);
		}

		$flowId = (int)($fields['FLOW_ID'] ?? null);

		if (!self::shouldStartGeneration($flowId))
		{
			return new EventResult(EventResult::SUCCESS);
		}

		Collector::execute($flowId);

		return new EventResult(EventResult::SUCCESS);
	}

	private static function shouldStartGeneration(int $flowId): bool
	{
		if ($flowId <= 0)
		{
			return false;
		}

		$advice = (new AdviceProvider())->get($flowId);

		if ($advice !== null)
		{
			return false;
		}

		$totalTasks = (new TaskProvider())->getTotalTasks([$flowId])[$flowId];

		if ($totalTasks < Configuration::getMinFlowTasksCount())
		{
			return false;
		}

		return true;
	}
}
