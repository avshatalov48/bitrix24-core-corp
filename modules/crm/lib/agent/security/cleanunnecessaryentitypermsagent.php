<?php

namespace Bitrix\Crm\Agent\Security;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Agent\Security\EntityPerms\CleanUnnecessaryEntityPerms;

class CleanUnnecessaryEntityPermsAgent extends AgentBase
{
	public const AGENT_DONE_STOP_IT = false;

	public const AGENT_CONTINUE = true;

	public static function doRun(): bool
	{
		$continue = (new CleanUnnecessaryEntityPerms())->execute();

		if ($continue)
		{
			return self::AGENT_CONTINUE;
		}

		return self::AGENT_DONE_STOP_IT;
	}
}