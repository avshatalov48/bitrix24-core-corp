<?php

namespace Bitrix\Crm\Agent\Activity;

use Bitrix\Crm\Agent\Activity\FastSearch\ClarifyConfigurable;
use Bitrix\Crm\Agent\AgentBase;

class FastSearchConfigurableSupportAgent extends AgentBase
{
	public const AGENT_DONE_STOP_IT = false;

	public const AGENT_CONTINUE = true;

	public static function doRun(): bool
	{
		$continue = (new ClarifyConfigurable())->execute();

		if ($continue)
		{
			return self::AGENT_CONTINUE;
		}

		return self::AGENT_DONE_STOP_IT;
	}

}