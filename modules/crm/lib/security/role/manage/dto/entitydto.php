<?php

namespace Bitrix\Crm\Security\Role\Manage\DTO;

use Bitrix\Crm\Security\Role\Manage\Permissions\Permission;

class EntityDTO
{
	public function __construct(
		private string $code,
		private string $name,
		private array $fields,
		/** @var Permission[] */
		private array $permissions,
		private ?string $description = null,
		private ?string $iconCode = null,
		private ?string $iconColor = null,
	)
	{
	}

	public function code(): string
	{
		return $this->code;
	}

	public function name(): string
	{
		return $this->name;
	}

	public function fields(): array
	{
		return $this->fields;
	}

	public function description(): ?string
	{
		return $this->description;
	}

	public function iconCode(): ?string
	{
		return $this->iconCode;
	}

	public function iconColor(): ?string
	{
		return $this->iconColor;
	}

	/**
	 * @return Permission[]
	 */
	public function permissions(): array
	{
		return $this->permissions;
	}

	public function toArray(): array
	{
		return [
			'code' => $this->code(),
			'name' => $this->name(),
			'fields' => empty($this->fields) ? null : $this->fields,
			'perms' => array_map(fn(Permission $perm) => $perm->toArray(), $this->permissions),
		];
	}
}