<?php

namespace Bitrix\Crm\Agent\Security\Service\PermissionTransmitter;

use Bitrix\Crm\Agent\Security\Service\PermissionTransmitter;
use Bitrix\Crm\Security\Role\Model\EO_Role;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission;
use Bitrix\Crm\Security\Role\Model\RolePermissionTable;

final class Copy implements PermissionTransmitter
{
	public function __construct(
		private readonly EO_Role $targetRole,
		private readonly array $overrideFields,
	)
	{
	}

	public function transmit(EO_RolePermission $permission): bool
	{
		$copy = RolePermissionTable
			::createObject(false)
			->setEntity($permission->getEntity())
			->setField($permission->getField())
			->setFieldValue($permission->getFieldValue())
			->setPermType($permission->getPermType())
			->setAttr($permission->getAttr())
			->setSettings($permission->getSettings())
		;

		foreach ($this->overrideFields as $fieldName => $value)
		{
			$copy->set($fieldName, $value);
		}

		$this->targetRole->addToPermissions($copy);

		return true;
	}
}
