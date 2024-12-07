<?php

namespace Bitrix\Tasks\Flow\Grid\Column;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Provider\FlowProvider;

final class BIAnalytics extends Column
{
	public function __construct()
	{
		$this->init();
	}

	public function prepareData(Flow $flow, array $params = []): int
	{
		return (new FlowProvider())->getEfficiency($flow);
	}

	private function init(): void
	{
		$this->id = 'BI_ANALYTICS';
		$this->name = Loc::getMessage('TASKS_FLOW_LIST_COLUMN_BIANALYTICS');
		$this->sort = '';
		$this->default = true;
		$this->editable = false;
		$this->resizeable = false;
		$this->width = null;
	}
}