<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions;

use Bitrix\Main\Access\Permission\PermissionDictionary;

/**
 * Base class for permissions
 * Don't forget to add new permission to list RoleManagementModelBuilder::allPermissions
 */
abstract class Permission
{
	public function __construct(private readonly array $variants = [])
	{

	}

	abstract public function code(): string;

	abstract public function name(): string;

	abstract public function canAssignPermissionToStages(): bool;

	public function variants(): array
	{
		return $this->variants;
	}

	public function toArray(): array
	{
		return [
			'code' => $this->code(),
			'name' => $this->name(),
			'variants' => $this->variants(),
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
	 */
	public function controlType(): string
	{
		return PermissionDictionary::TYPE_VARIABLES;
	}

	public function getMaxSettingsValue(): array
	{
		return [];
	}

	public function getMinSettingsValue(): array
	{
		return [];
	}
}
