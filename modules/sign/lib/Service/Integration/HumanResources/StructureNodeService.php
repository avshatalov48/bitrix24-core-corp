<?php

namespace Bitrix\Sign\Service\Integration\HumanResources;

use Bitrix\HumanResources\Contract\Service\NodeMemberService;
use Bitrix\HumanResources\Integration\UI\RoleProvider;
use Bitrix\HumanResources\Type\StructureRole;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

final class StructureNodeService
{
	private ?NodeMemberService $nodeMemberService = null;

	public function __construct()
	{
		if (!$this->isAvailable())
		{
			return;
		}

		$this->nodeMemberService = Container::getNodeMemberService();
	}

	public function getNearestUserIdByEmployeeUserIdAndRoleId(int $employeeUserId, int $roleId): int
	{
		if (!$this->isAvailable())
		{
			return 0;
		}

		$role = StructureRole::tryFrom($roleId);

		if ($role === null)
		{
			return 0;
		}

		return (int)$this->nodeMemberService?->getNearestUserIdByEmployeeUserIdAndRole($employeeUserId, $role);
	}

	public function getRoleIdByName(string $name): ?int
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		if (empty($name))
		{
			return null;
		}

		$role = null;

		try
		{
			$role = StructureRole::fromName($name);
		}
		catch (\Throwable)
		{
		}

		return $role?->value;
	}

	/**
	 * @return array<string, int>
	 */
	public function getRoleList(): array
	{
		if (!$this->isAvailable())
		{
			return [];
		}

		return [
			StructureRole::HEAD->name => StructureRole::HEAD->value,
			StructureRole::DEPUTY_HEAD->name => StructureRole::DEPUTY_HEAD->value,
		];
	}

	public function getRoleNameById(int $id): ?string
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		if ($id < 1)
		{
			return null;
		}

		$role = StructureRole::tryFrom($id);

		return $role?->name;
	}

	public function getRoleTitleById(int $id): ?string
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		if ($id < 1)
		{
			return null;
		}

		$role = StructureRole::tryFrom($id);

		if ($role === null)
		{
			return null;
		}

		$this->loadTranslations(RoleProvider::class);

		return match ($role) {
			StructureRole::HEAD => Loc::getMessage('HUMANRESOURCES_INTEGRATION_ROLE_PROVIDER_HEAD') ?? '',
			StructureRole::DEPUTY_HEAD => Loc::getMessage('HUMANRESOURCES_INTEGRATION_ROLE_PROVIDER_DEPUTY_HEAD') ?? '',
			default => null,
		};
	}

	private function loadTranslations(string $className, ?string $language = null): void
	{
		$reflector = new \ReflectionClass($className);
		$langFile = $reflector->getFileName();
		Loc::loadLanguageFile($langFile, $language);
	}

	public function isAvailable(): bool
	{
		return Loader::includeModule('humanresources') && class_exists(
				\Bitrix\HumanResources\Builder\Structure\NodeMemberDataBuilder::class);
	}
}
