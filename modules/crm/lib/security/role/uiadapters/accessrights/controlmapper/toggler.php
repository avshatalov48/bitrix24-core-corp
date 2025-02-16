<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper;

use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Access\Permission\PermissionDictionary;

class Toggler extends BaseControlMapper
{
	private ?bool $defaultValue = null;

	public function getType(): string
	{
		return PermissionDictionary::TYPE_TOGGLER;
	}

	public function getValueForUi(?string $attr, ?array $settings): ?string
	{
		return !empty($attr) ? '1' : '0';
	}

	public function getAttrFromUiValue(array $value): ?string
	{
		if (empty($value))
		{
			return UserPermissions::PERMISSION_NONE;
		}
		$value = (int)array_pop($value);

		return $value === 1 ? UserPermissions::PERMISSION_ALL : UserPermissions::PERMISSION_NONE;
	}

	public function getSettingsFromUiValue(array $value): ?array
	{
		return null;
	}

	public function getMinValue(): string
	{
		return '0';
	}

	public function getMaxValue(): string
	{
		return '1';
	}

	public function getExtraOptions(): array
	{
		if (is_bool($this->defaultValue))
		{
			$default = $this->defaultValue ? '1' : '0';
		}
		else
		{
			$default = null;
		}

		return [
			'defaultValue' => $default,
		];
	}

	public function setDefaultValue(bool $default): self
	{
		$this->defaultValue = $default;

		return $this;
	}
}
