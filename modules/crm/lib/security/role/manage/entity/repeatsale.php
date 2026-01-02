<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;
use Bitrix\Crm\Security\Role\Manage\Permissions\CopilotCallAssessment\Read;
use Bitrix\Crm\Security\Role\Manage\Permissions\CopilotCallAssessment\Write;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;

final class RepeatSale implements PermissionEntity
{
	private function permissions(): array
	{
		return [
			new Read(PermissionAttrPresets::allowedYesNo()),
			new Write(PermissionAttrPresets::allowedYesNo()),
		];
	}

	public function make(): array
	{
		$availabilityChecker = Container::getInstance()->getRepeatSaleAvailabilityChecker();
		if (!$availabilityChecker->isAvailable() || !$availabilityChecker->hasPermission())
		{
			return [];
		}

		$name = (string)Loc::getMessage('CRM_SECURITY_ROLE_ENTITY_TYPE_CRM_REPEAT_SALE');

		return [
			new EntityDTO('RS', $name, [], $this->permissions()),
		];
	}
}