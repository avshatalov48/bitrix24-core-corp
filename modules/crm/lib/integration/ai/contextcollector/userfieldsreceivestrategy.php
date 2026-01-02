<?php

namespace Bitrix\Crm\Integration\AI\ContextCollector;

use Bitrix\Crm\Field\Collection;

interface UserFieldsReceiveStrategy
{
	public function getAll(): Collection;
}
