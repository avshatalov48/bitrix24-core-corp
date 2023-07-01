<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Action;

use Bitrix\Crm\Service\Timeline\Layout\Action;

class RunAjaxAction extends Action
{
	protected string $action;

	public function __construct(string $action)
	{
		$this->action = $action;
	}

	public function getAction(): string
	{
		return $this->action;
	}

	public function toArray(): array
	{
		return [
			'type' => 'runAjaxAction',
			'value' => $this->getAction(),
			'actionParams' => $this->getActionParams(),
			'animation' => $this->getAnimation(),
			'analytics' => $this->getAnalytics(),
		];
	}
}
