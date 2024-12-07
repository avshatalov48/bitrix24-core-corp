<?php

namespace Bitrix\Tasks\Flow\Grid\Column;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Grid\Preload\AverageCompletedTimePreloader;
use Bitrix\Tasks\Flow\Grid\Preload\CompletedTaskPreloader;
use Bitrix\Tasks\Flow\Task\Status;

final class Completed extends Column
{
	private CompletedTaskPreloader $taskPreloader;
	private AverageCompletedTimePreloader $timePreloader;

	public function __construct()
	{
		$this->init();
	}

	public function prepareData(Flow $flow, array $params = []): array
	{
		return [
			'flow' => $flow,
			'users' => $this->taskPreloader->get($flow->getId()),
			'date' => $this->timePreloader->get($flow->getId())?->getFormatted(),
		];
	}

	private function init(): void
	{
		$this->id = Status::FLOW_COMPLETED;
		$this->name = Loc::getMessage('TASKS_FLOW_LIST_COLUMN_COMPLETED');
		$this->sort = '';
		$this->default = true;
		$this->editable = false;
		$this->resizeable = false;
		$this->width = null;
		$this->align = 'center';
		$this->class = 'tasks-flow__grid-column-center';

		$this->taskPreloader = new CompletedTaskPreloader();
		$this->timePreloader = new AverageCompletedTimePreloader();
	}
}
