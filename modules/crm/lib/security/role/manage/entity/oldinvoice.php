<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;
use Bitrix\Crm\Settings\InvoiceSettings;
use CCrmOwnerType;

class OldInvoice implements PermissionEntity
{
	private function permissions(): array
	{
		return PermissionAttrPresets::crmEntityPreset();
	}

	public function make(): array
	{
		if (!InvoiceSettings::getCurrent()->isOldInvoicesEnabled())
		{
			return [];
		}

		$entityDto = new EntityDTO(
			CCrmOwnerType::InvoiceName,
			CCrmOwnerType::GetDescription(CCrmOwnerType::Invoice),
			[],
			$this->permissions(),
			null,
			'invoice',
			'#0B66C3',
		);

		return [$entityDto];
	}
}
