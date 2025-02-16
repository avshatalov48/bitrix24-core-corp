<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper;

use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Access\Permission\PermissionDictionary;

class MultiVariables extends BaseControlMapper
{
	private bool $disableSelectAll = false;

	public function getType(): string
	{
		return PermissionDictionary::TYPE_MULTIVARIABLES;
	}

	public function getMinValue(): array
	{
		return $this->permission->getMinSettingsValue();
	}

	public function getMaxValue(): array
	{
		return $this->permission->getMaxSettingsValue();
	}

	public function getExtraOptions(): array
	{
		return [
			'disableSelectAll' => $this->disableSelectAll,
		];
	}

	public function getValueForUi(?string $attr, ?array $settings): array
	{
		return (array)$settings;
	}

	public function getAttrFromUiValue(array $value): ?string
	{
		return UserPermissions::PERMISSION_NONE;
	}

	public function getSettingsFromUiValue(array $value): ?array
	{
		return $value;
	}

	public function setDisableSelectAll(bool $disableSelectAll): self
	{
		$this->disableSelectAll = $disableSelectAll;

		return $this;
	}
}
