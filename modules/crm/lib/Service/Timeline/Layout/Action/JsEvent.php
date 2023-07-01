<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Action;

use Bitrix\Crm\Service\Timeline\Layout\Action;

class JsEvent extends Action
{
	protected string $event;

	public function __construct(string $event)
	{
		$this->event = $event;
	}

	public function getEvent(): string
	{
		return $this->event;
	}

	public function toArray(): array
	{
		return [
			'type' => 'jsEvent',
			'value' => $this->getEvent(),
			'actionParams' => $this->getActionParams(),
			'animation' => $this->getAnimation(),
			'analytics' => $this->getAnalytics(),
		];
	}
}
