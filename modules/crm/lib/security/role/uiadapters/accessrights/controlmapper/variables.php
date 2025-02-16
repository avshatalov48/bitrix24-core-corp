<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper;

use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Access\Permission\PermissionDictionary;

class Variables extends BaseControlMapper
{
	private array $uiValueToAttrMap = [];

	public function getType(): string
	{
		return PermissionDictionary::TYPE_VARIABLES;
	}

	public function getValueForUi(?string $attr, ?array $settings): string
	{
		return (string)$attr;
	}

	public function getAttrFromUiValue(array $value): ?string
	{
		$value = array_pop($value);
		if (empty($value))
		{
			return UserPermissions::PERMISSION_NONE;
		}

		if (array_key_exists($value, $this->uiValueToAttrMap))
		{
			return $this->uiValueToAttrMap[$value];
		}

		return $value;
	}

	public function getSettingsFromUiValue(array $value): ?array
	{
		return null;
	}

	public function getMinValue(): string
	{
		return $this->permission->getMinAttributeValue();
	}

	public function getMaxValue(): string
	{
		return $this->permission->getMaxAttributeValue();
	}

	public function addAttrMapping(string $uiValue, ?string $attr): self
	{
		$this->uiValueToAttrMap[$uiValue] = $attr;

		return $this;
	}
}
