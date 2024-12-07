<?php

namespace Bitrix\Tasks\Flow\Grid\Preload;

use Bitrix\Tasks\Flow\FlowRegistry;

class AccessPreloader
{
	final public function preload(int ...$flowIds): void
	{
		FlowRegistry::getInstance()->load($flowIds);
	}
}