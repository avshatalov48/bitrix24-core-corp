<?php

namespace Bitrix\Tasks\Flow\Grid\Action;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Flow;

class Activate extends Action
{
	public const ID = 'activate';

	public function __construct()
	{
		$this->id = self::ID;
		$this->data = [];
		$this->default = false;
		$this->href = '';
		$this->onclick = '';
		$this->className = '';
	}

	public function prepareData(Flow $flow, array $params = []): void
	{
		$this->text = (
			$flow->isActive()
				? Loc::getMessage('TASKS_FLOW_LIST_ACTION_ACTIVATE_OFF')
				: Loc::getMessage('TASKS_FLOW_LIST_ACTION_ACTIVATE_ON')
		);

		$this->data = [
			'flowId' => $flow->getId(),
			'demo' => $flow->isDemo(),
			'isActive' => $flow->isActive(),
		];
	}
}
