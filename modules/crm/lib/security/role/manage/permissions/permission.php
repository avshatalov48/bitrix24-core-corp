<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions;

use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\BaseControlMapper;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\Variables;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Variants;

/**
 * Base class for permissions
 * Don't forget to add new permission to list RoleManagementModelBuilder::allPermissions
 */
abstract class Permission
{
	public function __construct(private ?Variants $variants = null, private ?BaseControlMapper $controlMapper = null)
	{
		if (!isset($this->controlMapper))
		{
			$this->controlMapper = $this->createDefaultControlMapper();
		}
		$this->controlMapper->setPermission($this);
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
		return $this->createDefaultControlMapper()->getType();
	}

	public function getMaxSettingsValue(): array
	{
		return [];
	}

	public function getMinSettingsValue(): array
	{
		return [];
	}

	public function getControlMapper(): BaseControlMapper
	{
		return $this->controlMapper;
	}

	protected function createDefaultControlMapper(): BaseControlMapper
	{
		return new Variables();
	}

	public function getObserverDefaultAttributeValue(): ?string
	{
		return null;
	}

	public function getHeadDefaultAttributeValue(): ?string
	{
		return \Bitrix\Crm\Service\UserPermissions::PERMISSION_ALL;
	}

	public function getDeputyDefaultAttributeValue(): ?string
	{
		return \Bitrix\Crm\Service\UserPermissions::PERMISSION_DEPARTMENT;
	}

	public function getManagerDefaultAttributeValue(): ?string
	{
		return \Bitrix\Crm\Service\UserPermissions::PERMISSION_NONE;
	}

	public function getObserverDefaultSettings(): array
	{
		return [];
	}

	public function getHeadDefaultSettings(): array
	{
		return [];
	}

	public function getDeputyDefaultSettings(): array
	{
		return [];
	}

	public function getManagerDefaultSettings(): array
	{
		return [];
	}
}
