<?php

namespace Bitrix\Tasks\Flow\Grid\Action;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Flow;

class Edit extends Action
{
	public const ID = 'edit';

	public function __construct()
	{
		$this->id = self::ID;
		$this->text = Loc::getMessage('TASKS_FLOW_LIST_ACTION_EDIT');
		$this->data = [];
		$this->default = false;
		$this->href = '';
		$this->onclick = '';
		$this->className = '';
	}

	public function prepareData(Flow $flow, array $params = []): void
	{
		$this->data = [
			'flowId' => $flow->getId(),
			'demo' => $flow->isDemo(),
		];
	}
}
