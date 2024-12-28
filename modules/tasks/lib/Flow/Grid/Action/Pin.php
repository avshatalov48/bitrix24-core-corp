<?php

namespace Bitrix\Tasks\Flow\Grid\Action;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Flow;

class Pin extends Action
{
	public const ID = 'pin';

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
		$params['isPinned']
			? Loc::getMessage('TASKS_FLOW_LIST_ACTION_UNPIN')
			: Loc::getMessage('TASKS_FLOW_LIST_ACTION_PIN')
		);

		$this->data = [
			'flowId' => $flow->getId(),
			'isPinned' => $params['isPinned'],
		];
	}
}
