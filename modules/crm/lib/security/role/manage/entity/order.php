<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;
use Bitrix\Crm\Service\Container;
use CCrmOwnerType;

class Order implements PermissionEntity
{
	private function permissions(): array
	{
		return array_merge(
			PermissionAttrPresets::crmEntityPresetAutomation(),
			PermissionAttrPresets::crmEntityKanbanHideSum(),
		);
	}

	/**
	 * @return EntityDTO[]
	 */
	public function make(): array
	{
		$name = Container::getInstance()->getFactory(CCrmOwnerType::Order)->getEntityDescription();

		return [
			new EntityDTO(
				'ORDER',
				$name,
				[],
				$this->permissions(),
				null,
				'crm-payment',
				'--ui-color-accent-brown'
			),
		];
	}
}
