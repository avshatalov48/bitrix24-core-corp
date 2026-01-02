<?php

namespace Bitrix\Crm\Agent\Security;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Agent\Security\Service\RoleSeparatorFactory;
use Bitrix\Crm\Agent\Security\Service\SeparateAllRolesOperation;

final class SeparateRolesAgent extends AgentBase
{
	public static function activateNewPermissionsInterface(): string
	{
		self::doRun();

		return '';
	}

	public static function doRun(): bool
	{
		return (new SeparateAllRolesOperation())->run(
			(new RoleSeparatorFactory())->getAll(),
		);
	}
}
