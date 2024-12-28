<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Event;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Integration\AI\Configuration;
use Bitrix\Tasks\Flow\Integration\AI\FlowCopilotFeature;
use Bitrix\Tasks\Flow\Integration\AI\Provider\CollectedDataProvider;
use Bitrix\Tasks\Flow\Integration\AI\Stepper\Collector;
use Bitrix\Tasks\Flow\Provider\TaskProvider;

final class EfficiencyListener
{
	private int $tasksCount;
	private ?int $efficiencyBefore;
	private Flow $flow;

	private TaskProvider $taskProvider;
	private CollectedDataProvider $collectedDataProvider;

	public function onFlowEfficiencyChanged(Event $event): EventResult
	{
		if (!FlowCopilotFeature::isOn() || !FlowCopilotFeature::isAdviceAutoGenerationOn())
		{
			return new EventResult(EventResult::SUCCESS);
		}

		$flow = $event->getParameter('flow');
		if (!$flow instanceof Flow)
		{
			return new EventResult(EventResult::ERROR);
		}

		$this->flow = $flow;

		$this->init();

		if ($this->shouldStartGeneration())
		{
			$this->runDataCollector();
		}

		return new EventResult(EventResult::SUCCESS);
	}

	public function shouldStartGeneration(): bool
	{
		$shouldStartGeneration = $this->isEnoughTasks();

		if ($shouldStartGeneration && $this->efficiencyBefore)
		{
			$shouldStartGeneration = $this->isEfficiencyChangedEnough() || $this->isEfficiencySwitched();
		}

		return $shouldStartGeneration;
	}

	private function isEnoughTasks(): bool
	{
		return $this->tasksCount >= Configuration::getMinFlowTasksCount();
	}

	private function isEfficiencyChangedEnough(): bool
	{
		$neededEfficiencyChange = $this->getNeededEfficiencyChange();

		return abs($this->flow->getEfficiency() - $this->efficiencyBefore) >= $neededEfficiencyChange;
	}

	private function isEfficiencySwitched(): bool
	{
		$maxValueForLowEfficiency = Configuration::getMaxValueForLowEfficiency();
		$flowEfficiency = $this->flow->getEfficiency();

		$switchedFromHighToLow =
			$this->efficiencyBefore > $maxValueForLowEfficiency
			&& $flowEfficiency <= $maxValueForLowEfficiency
		;

		$switchedFromLowToHigh =
			$this->efficiencyBefore <= $maxValueForLowEfficiency
			&& $flowEfficiency > $maxValueForLowEfficiency
		;

		return $switchedFromHighToLow || $switchedFromLowToHigh;
	}

	private function runDataCollector(): void
	{
		Collector::execute($this->flow->getId());
	}

	private function getNeededEfficiencyChange(): int
	{
		$efficiencyChangesByTasksCount = Configuration::getMinEfficiencyChangesByTasksCount();
		krsort($efficiencyChangesByTasksCount);

		foreach ($efficiencyChangesByTasksCount as $tasksCount => $efficiencyChange)
		{
			if ($this->tasksCount >= $tasksCount)
			{
				return $efficiencyChange;
			}
		}

		return 100;
	}

	private function init(): void
	{
		$this->taskProvider = new TaskProvider();
		$this->collectedDataProvider = new CollectedDataProvider();

		$this->fillFlowTasksCount();
		$this->fillEfficiencyBefore();
	}

	private function fillFlowTasksCount(): void
	{
		$this->tasksCount = $this->taskProvider->getTotalTasks([$this->flow->getId()])[$this->flow->getId()];
	}

	private function fillEfficiencyBefore(): void
	{
		$json = $this->collectedDataProvider->get($this->flow->getId())->getData();

		$this->efficiencyBefore =
			isset($json['flow']['team_efficiency_percentage'])
				? (int)($json['flow']['team_efficiency_percentage'])
				: null
		;
	}
}
