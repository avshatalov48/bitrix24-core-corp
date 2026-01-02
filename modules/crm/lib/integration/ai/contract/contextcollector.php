<?php

namespace Bitrix\Crm\Integration\AI\Contract;

interface ContextCollector
{
	public function collect(): array;
}
