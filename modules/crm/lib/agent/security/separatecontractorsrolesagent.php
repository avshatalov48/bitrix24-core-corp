<?php

namespace Bitrix\Crm\Agent\Security;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Agent\Security\Service\RoleSeparatorFactory;
use Bitrix\Crm\Agent\Security\Service\SeparateAllRolesOperation;
use CAgent;

final class SeparateContractorsRolesAgent extends AgentBase
{
	public static function doRun(): bool
	{
		if (self::isSeparateAllRolesAgentRunning())
		{
			return false;
		}

		$contractorSeparator = (new RoleSeparatorFactory())->getContractorSeparator();
		if ($contractorSeparator === null)
		{
			return false;
		}

		return (new SeparateAllRolesOperation())->run([$contractorSeparator]);
	}

	private static function isSeparateAllRolesAgentRunning(): bool
	{
		$agent = CAgent::GetList([], [
			'MODULE_ID' => 'crm',
			'NAME' => '%Bitrix\Crm\Agent\Security\SeparateRolesAgent%',
		])->Fetch();

		return is_array($agent);
	}
}
