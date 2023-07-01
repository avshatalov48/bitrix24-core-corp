<?php

namespace Bitrix\Crm\Agent\Activity;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Counter\Lighter\LighterFactory;

class LightCounterAgent extends AgentBase
{
	public static function doRun()
	{
		$lighter = LighterFactory::make();
		$lighter->execute();

		return true;
	}

}
