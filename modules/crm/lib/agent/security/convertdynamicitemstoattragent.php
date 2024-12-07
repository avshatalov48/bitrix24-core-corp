<?php

namespace Bitrix\Crm\Agent\Security;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Agent\Security\DynamicTypes\DynamicTypesProcess;

class ConvertDynamicItemsToAttrAgent extends AgentBase
{
	public static function doRun(): bool
	{
		return (new DynamicTypesProcess)->execute();
	}
}
