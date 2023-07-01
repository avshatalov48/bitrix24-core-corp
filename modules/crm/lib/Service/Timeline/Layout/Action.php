<?php

namespace Bitrix\Crm\Service\Timeline\Layout;

use Bitrix\Crm\Service\Timeline\Layout\Action\Analytics;
use Bitrix\Crm\Service\Timeline\Layout\Action\Animation;

abstract class Action extends Base
{
	protected ?array $actionParams = null;
	protected ?Animation $animation = null;
	protected ?Analytics $analytics = null;

	public function getActionParams(): ?array
	{
		return $this->actionParams;
	}

	public function addActionParamBoolean(string $paramName, ?bool $paramValue): self
	{
		$this->actionParams[$paramName] = $paramValue;

		return $this;
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

	public function addActionParamArray(string $paramName, ?array $paramValue): self
	{
		$this->actionParams[$paramName] = $paramValue;

		return $this;
	}

	public function getAnimation(): ?Animation
	{
		return $this->animation;
	}


	public function setAnimation(?Animation $animation): self
	{
		$this->animation = $animation;

		return $this;
	}

	public function getAnalytics(): ?Analytics
	{
		return $this->analytics;
	}

	public function setAnalytics(?Analytics $analytics): self
	{
		$this->analytics = $analytics;

		return $this;
	}
}
