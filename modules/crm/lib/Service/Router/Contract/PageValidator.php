<?php

namespace Bitrix\Crm\Service\Router\Contract;

interface PageValidator
{
	public function isAvailable(): bool;

	public function showError(): void;
}
