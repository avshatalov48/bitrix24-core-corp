<?php

namespace Bitrix\Tasks\Flow\Grid\Column;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Flow;

class CreateTask extends Column
{
	public function __construct()
	{
		$this->init();
	}

	public function prepareData(Flow $flow, array $params = []): Flow
	{
		return $flow;
	}

	private function init(): void
	{
		$this->id = 'CREATE_TASK';
		$this->name = Loc::getMessage('TASKS_FLOW_LIST_COLUMN_CREATE_TASK');
		$this->sort = '';
		$this->default = true;
		$this->editable = false;
		$this->resizeable = false;
		$this->width = 200;
	}
}
