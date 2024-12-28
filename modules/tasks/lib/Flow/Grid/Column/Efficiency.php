<?php

namespace Bitrix\Tasks\Flow\Grid\Column;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Grid\Preload\TotalTasksCountPreloader;
use Bitrix\Tasks\Flow\Integration\AI\Configuration;
use Bitrix\Tasks\Flow\Provider\FlowProvider;

final class Efficiency extends Column
{
	protected TotalTasksCountPreloader $totalTasksCount;

	public function __construct()
	{
		$this->init();
	}

	public function prepareData(Flow $flow, array $params = []): array
	{
		$totalTasksCount = $this->totalTasksCount->get($flow->getId());
		$isEnoughTasksForCopilot = $totalTasksCount >= Configuration::getMinFlowTasksCount();

		return [
			'flow' => $flow,
			'efficiency' => (new FlowProvider())->getEfficiency($flow),
			'isEnoughTasksForCopilot' => $isEnoughTasksForCopilot,
		];
	}

	private function init(): void
	{
		$this->id = 'EFFICIENCY';
		$this->name = Loc::getMessage('TASKS_FLOW_LIST_COLUMN_EFFICIENCY');
		$this->sort = '';
		$this->default = true;
		$this->editable = false;
		$this->resizeable = false;
		$this->width = null;
		$this->align = 'center';
		$this->class = 'tasks-flow__grid-column-center';
		$this->totalTasksCount = new TotalTasksCountPreloader();
	}
}