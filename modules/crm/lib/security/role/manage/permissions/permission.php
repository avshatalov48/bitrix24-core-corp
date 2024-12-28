<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions;

use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlType\BaseControlType;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlType\Variables;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Variants;

/**
 * Base class for permissions
 * Don't forget to add new permission to list RoleManagementModelBuilder::allPermissions
 */
abstract class Permission
{
	public function __construct(private ?Variants $variants = null, private ?BaseControlType $controlType = null)
	{
		if (!isset($this->controlType))
		{
			$this->controlType = $this->createDefaultControlType();
		}
		$this->controlType->setPermission($this);
	}

	abstract public function code(): string;

	abstract public function name(): string;

	public function explanation(): ?string
	{
		return null;
	}

	abstract public function canAssignPermissionToStages(): bool;

	public function variants(): ?Variants
	{
		return $this->variants;
	}

	public function setVariants(Variants $variants): void
	{
		$this->variants = $variants;
	}

	public function toArray(): array
	{
		return [
			'code' => $this->code(),
			'name' => $this->name(),
			'variants' => $this->variants()?->toArray(),
			'canAssignPermissionToStages' => $this->canAssignPermissionToStages(),
			'defaultAttribute' => $this->getDefaultAttribute(),
			'defaultSettings' => $this->getDefaultSettings(),
		];
	}

	public function sortOrder(): ?int
	{
		return 999;
	}

	public function getDefaultAttribute(): ?string
	{
		return null;
	}

	public function getMaxAttributeValue(): ?string
	{
		return \Bitrix\Crm\Service\UserPermissions::PERMISSION_ALL;
	}

	public function getMinAttributeValue(): ?string
	{
		return \Bitrix\Crm\Service\UserPermissions::PERMISSION_NONE;
	}

	public function getDefaultSettings(): array
	{
		return [];
	}

	/**
	 * control type define way how to draw this permission on the frontend and convert it value from/to frontend
	 *
	 * @return string
	 * @deprecated see \Bitrix\Crm\Security\Role\Manage\Permissions\Permission::getControlType
	 */
	public function getControlTypeCode(): string
	{
		return $this->createDefaultControlType()->getType();
	}

	public function getMaxSettingsValue(): array
	{
		return [];
	}

	public function getMinSettingsValue(): array
	{
		return [];
	}

	public function getControlType(): BaseControlType
	{
		return $this->controlType;
	}

	protected function createDefaultControlType(): BaseControlType
	{
		return new Variables();
	}
}
