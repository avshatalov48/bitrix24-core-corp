<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Mixin;

use Bitrix\Crm\Service\Timeline\Layout\Action;

trait Actionable
{
	private ?Action $action = null;

	public function getAction(): ?Action
	{
		return $this->action;
	}

	public function setAction(?Action $action): self
	{
		$this->action = $action;
		return $this;
	}
}
