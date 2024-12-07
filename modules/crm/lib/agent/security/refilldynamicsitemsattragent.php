<?php

namespace Bitrix\Crm\Agent\Security;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Agent\Security\DynamicTypes\ReFillDynamicTypeAttr;

class RefillDynamicsItemsAttrAgent extends AgentBase
{
	public static function doRun(): bool
	{
		return (new ReFillDynamicTypeAttr)->execute();
	}
}