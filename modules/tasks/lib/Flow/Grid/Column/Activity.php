<?php

declare(strict_types=1);


namespace Bitrix\Tasks\Flow\Grid\Column;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Time\DatePresenter;

class Activity extends Column
{
	public function __construct()
	{
		$this->init();
	}

	public function prepareData(Flow $flow, array $params = []): string
	{
		return DatePresenter::beautify($flow->getActivityDate(), (int)($params['userId'] ?? 0));
	}

	private function init(): void
	{
		$this->id = 'ACTIVITY';
		$this->name = Loc::getMessage('TASKS_FLOW_LIST_COLUMN_ACTIVITY');
		$this->sort = '';
		$this->default = true;
		$this->editable = false;
		$this->resizeable = true;
		$this->width = null;
	}
}