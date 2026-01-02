<?php

namespace Bitrix\BIConnector\Access\Install;

use Bitrix\BIConnector\Access\Role\RoleDictionary;

class RoleMap
{
	/** @var Role\Base[] */
	private array $roles = [];

	public function __construct(bool $isNewPortal = false)
	{
		foreach (static::getDefaultMap() as $roleCode => $roleClass)
		{
			$this->add(
				new $roleClass(
					code: $roleCode,
					isNewPortal: $isNewPortal,
				),
			);
		}
	}

	public function add(Role\Base $role): self
	{
		$this->roles[] = $role;

		return $this;
	}

	/**
	 * @return Role\Base[]
	 */
	public function getRoles(): array
	{
		return $this->roles;
	}

	/**
	 * @return array<string, string>
	 */
	public static function getDefaultMap(): array
	{
		return [
			RoleDictionary::ROLE_ADMINISTRATOR => Role\Administrator::class,
			RoleDictionary::ROLE_ANALYST => Role\Analyst::class,
			RoleDictionary::ROLE_MANAGER => Role\Manager::class,
		];
	}
}
