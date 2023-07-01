<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Action;

use Bitrix\Crm\Service\Timeline\Layout\Action;

class CallRestBatch extends Action
{
	public function toArray(): array
	{
		return [
			'type' => 'callRestBatch',
			'value' => '',
			'actionParams' => $this->getActionParams(),
			'animation' => $this->getAnimation(),
			'analytics' => $this->getAnalytics(),
		];
	}
}
