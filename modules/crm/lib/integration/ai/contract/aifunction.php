<?php

namespace Bitrix\Crm\Integration\AI\Contract;

use Bitrix\Main\Result;

interface AIFunction
{
	public function isAvailable(): bool;

	public function invoke(...$args): Result;
}
