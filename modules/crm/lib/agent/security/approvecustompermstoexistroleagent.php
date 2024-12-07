<?php

namespace Bitrix\Crm\Agent\Security;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Security\EntityPermission\ApproveCustomPermsToExistRole;

class ApproveCustomPermsToExistRoleAgent extends AgentBase
{
	public const AGENT_DONE_STOP_IT = false;

	public const AGENT_CONTINUE = true;

	public static function doRun(): bool
	{
		$continue = (new ApproveCustomPermsToExistRole())->execute();

		if ($continue)
		{
			return self::AGENT_CONTINUE;
		}

		self::remove();

		return self::AGENT_DONE_STOP_IT;
	}

	private static function remove(): void
	{
		\CAgent::RemoveAgent(
			'Bitrix\\Crm\\Agent\\Recyclebin\\ApproveCustomPermsToExistRoleAgent::run();',
			'crm'
		);
	}
}