<?php

namespace Bitrix\Crm\Kanban\EntityActCounter;

class Builder
{
	public array $deadlines;
	public array $incoming = [];
	public array $counters = [];
	public array $activities = [];
	public array $incomingByResponsible = [];

	public function toCounterInfo(): CounterInfo
	{
		return new CounterInfo(
			$this->deadlines,
			$this->incoming,
			$this->counters,
			$this->incomingByResponsible
		);
	}
}