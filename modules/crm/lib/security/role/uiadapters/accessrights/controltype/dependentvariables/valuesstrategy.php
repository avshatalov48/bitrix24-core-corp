<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlType\DependentVariables;

use Bitrix\Crm\Security\Role\Manage\Permissions\Permission;

/**
 * @internal
 */
interface ValuesStrategy
{
	public function getMinValue(Permission $permission): array | string | null;

	public function getMaxValue(Permission $permission): array | string | null;

	public function getValueForUi(Permission $permission, ?string $attr, ?array $settings): array | string | null;

	public function getAttrFromUiValue(array $value): ?string;

	public function getSettingsFromUiValue(array $value): ?array;
}
