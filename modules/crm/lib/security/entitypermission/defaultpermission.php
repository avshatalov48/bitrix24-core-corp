<?php

namespace Bitrix\Crm\Security\EntityPermission;

use Bitrix\Crm\Security\Role\Manage\Permissions\Permission;
use JsonSerializable;

final class DefaultPermission implements JsonSerializable
{
	public static function createFromArray(array $data): ?self
	{
		if (isset($data['permissionClass']) && is_subclass_of($data['permissionClass'], Permission::class))
		{
			$permission = new $data['permissionClass'];

			return new self($permission, $data['attr'] ?? '', $data['settings'] ?? []);
		}

		return null;
	}

	public function __construct(
		private readonly Permission $permission,
		private readonly ?string $attr = '',
		private readonly ?array $settings = [],
	)
	{

	}

	public function getPermissionType(): string
	{
		return $this->permission->code();
	}

	public function getPermissionClass(): string
	{
		return $this->permission::class;
	}

	public function getAttr(): ?string
	{
		return $this->attr;
	}

	public function getSettings(): ?array
	{
		return $this->settings;
	}

	public function toArray(): array
	{
		return [
			'permissionClass' => $this->getPermissionClass(),
			'permissionType' => $this->getPermissionType(),
			'attr' => $this->getAttr(),
			'settings' => $this->getSettings(),
		];
	}

	public function jsonSerialize(): array
	{
		return [
			'permissionClass' => $this->getPermissionClass(),
			'permissionType' => $this->getPermissionType(),
			'attr' => $this->getAttr() ?? '',
			'settings' => $this->getSettings() ?? [],
		];
	}
}
