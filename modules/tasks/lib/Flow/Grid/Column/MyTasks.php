<?php

namespace Bitrix\Tasks\Flow\Grid\Column;

use Bitrix\Main\Grid\Counter\Color;
use Bitrix\Main\Grid\Counter\Type;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Grid\Preload\TasksCountPreloader;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Counter\Template\CounterStyle;

final class MyTasks extends Column
{
	private TasksCountPreloader $preloader;

	public function __construct()
	{
		$this->init();
	}

	public function hasCounter(): bool
	{
		return true;
	}

	public function prepareData(Flow $flow, array $params = []): array
	{
		return [
			'flowId' => $flow->getId(),
			'flowName' => $flow->getName(),
			'numberOfTasks' => $this->preloader->get($flow->getId()),
		];
	}

	public function getCounter(Flow $flow, int $userId): array
	{
		$counter = Counter::getInstance($userId);

		$counters = [
			'new_comments' => $counter->get(CounterDictionary::COUNTER_FLOW_TOTAL_COMMENTS, $flow->getId()),
			'expired' => $counter->get(CounterDictionary::COUNTER_FLOW_TOTAL_EXPIRED, $flow->getId()),
		];

		$colorMap = [
			CounterStyle::STYLE_GRAY => Color::GRAY,
			CounterStyle::STYLE_GREEN => Color::SUCCESS,
			CounterStyle::STYLE_RED => Color::DANGER,
		];
		$color = CounterStyle::STYLE_GRAY;
		if ($counters['new_comments'] > 0)
		{
			$color = CounterStyle::STYLE_GREEN;
		}
		if ($counters['expired'] > 0)
		{
			$color = CounterStyle::STYLE_RED;
		}

		return [
			'type' => Type::LEFT_ALIGNED,
			'color' => $colorMap[$color],
			'value' => array_sum($counters),
		];
	}

	private function init(): void
	{
		$this->id = 'MY_TASKS';
		$this->name = Loc::getMessage('TASKS_FLOW_LIST_COLUMN_MY_TASKS');
		$this->sort = '';
		$this->default = true;
		$this->editable = false;
		$this->resizeable = false;
		$this->width = 140;
		$this->align = 'right';
		$this->class = 'tasks-flow__grid-column-center';

		$this->preloader = new TasksCountPreloader();
	}
}
