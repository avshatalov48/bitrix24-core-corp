<?php

namespace Bitrix\Tasks\Flow\Grid\Column;

use Bitrix\Tasks\Flow\Flow;

final class Id extends Column
{
	public function __construct()
	{
		$this->init();
	}

	public function prepareData(Flow $flow, array $params = []): int
	{
		return $flow->getId();
	}

	private function init(): void
	{
		$this->id = 'ID';
		$this->name = 'ID';
		$this->sort = '';
		$this->default = false;
		$this->editable = false;
		$this->resizeable = true;
		$this->width = null;
	}
}
