<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlType;

use Bitrix\Crm\Security\Role\Manage\Permissions\Permission;

abstract class BaseControlType
{
	protected ?Permission $permission = null;

	public function setPermission(Permission $permission): void
	{
		$this->permission = $permission;
	}

	abstract public function getType(): string;
	abstract public function getValueForUi(?string $attr, ?array $settings);
	abstract public function getAttrFromUiValue(array $value): ?string;
	abstract public function getSettingsFromUiValue(array $value): ?array;
	abstract public function getMinValue(): string | array | null;
	abstract public function getMaxValue(): string | array | null;

	public function getExtraOptions(): array
	{
		return [];
	}


}
