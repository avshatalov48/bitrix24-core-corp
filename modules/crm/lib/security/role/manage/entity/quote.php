<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;
use Bitrix\Crm\Service\Container;
use CCrmOwnerType;
use CCrmStatus;

class Quote implements PermissionEntity
{
	private function permissions(): array
	{
		return array_merge(
			PermissionAttrPresets::crmEntityPreset(),
			PermissionAttrPresets::crmEntityKanbanHideSum(),
			PermissionAttrPresets::crmStageTransition(CCrmStatus::GetStatusListEx('QUOTE_STATUS'))
		);
	}

	/**
	 * @return EntityDTO[]
	 */
	public function make(): array
	{
		$name = Container::getInstance()->getFactory(CCrmOwnerType::Quote)->getEntityDescription();
		return [
			new EntityDTO(
				'QUOTE',
				$name,
				[],
				$this->permissions(),
				null,
				'commercial-offer',
				'--ui-color-accent-aqua',
			)
		];
	}
}
