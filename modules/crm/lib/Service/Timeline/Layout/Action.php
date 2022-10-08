<?php

namespace Bitrix\Crm\Service\Timeline\Layout;

abstract class Action extends Base
{
	protected ?array $actionParams = null;

	public function getActionParams(): ?array
	{
		return $this->actionParams;
	}

	public function addActionParamString(string $paramName, ?string $paramValue): self
	{
		$this->actionParams[$paramName] = $paramValue;

		return $this;
	}

	public function addActionParamInt(string $paramName, ?int $paramValue): self
	{
		$this->actionParams[$paramName] = $paramValue;

		return $this;
	}
}
