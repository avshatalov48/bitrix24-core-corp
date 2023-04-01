<?php

namespace Bitrix\Crm\Filter;

trait ForceUseFactoryTrait
{
	protected bool $forceUseFactory = false;
	public function setForceUseFactory(bool $value): void
	{
		$this->forceUseFactory = $value;
	}

	public function isForceUseFactory(): bool
	{
		return $this->forceUseFactory;
	}
}
