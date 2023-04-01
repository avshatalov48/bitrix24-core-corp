<?php

namespace Bitrix\Crm\Filter;

interface FactoryOptionable
{
	public function setForceUseFactory(bool $value): void;
	public function isForceUseFactory(): bool;
}
