<?php

namespace Bitrix\HumanResources\Service;

use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\Main\Access\AccessCode;

class RoleHelperService implements Contract\Service\RoleHelperService
{
	private readonly Contract\Repository\RoleRepository $roleRepository;

	/** @var array<string, ?Item\Role> */
	private static array $roleByXmlIdCache = [];

	/** @var array<int, ?Item\Role> */
	private static array $roleByIdCache = [];

	public function __construct(
		?Contract\Repository\RoleRepository $roleRepository = null,
	)
	{
		$this->roleRepository = $roleRepository ?? Container::getRoleRepository();
	}

	public function getById(int $roleId): ?Item\Role
	{
		if (empty(self::$roleByIdCache[$roleId]))
		{
			$role = $this->roleRepository->getById($roleId);
			if ($role)
			{
				$this->saveToCache($role);
			}
		}

		$value = self::$roleByIdCache[$roleId] ?? null;
		if (is_object($value))
		{
			return clone $value;
		}

		return $value;
	}

	public function getEmployeeRoleId(): ?int
	{
		return $this->getRoleByXmlIdWithCache('EMPLOYEE')?->id;
	}

	public function getHeadRoleId(): ?int
	{
		return $this->getRoleByXmlIdWithCache('HEAD')?->id;
	}

	public function getDeputyRoleId(): ?int
	{
		return $this->getRoleByXmlIdWithCache('DEPUTY_HEAD')?->id;
	}

	public function getTeamHeadRoleId(): ?int
	{
		return $this->getRoleByXmlIdWithCache('TEAM_HEAD')?->id;
	}

	public function getTeamDeputyRoleId(): ?int
	{
		return $this->getRoleByXmlIdWithCache('TEAM_DEPUTY_HEAD')?->id;
	}

	public function getTeamEmployeeRoleId(): ?int
	{
		return $this->getRoleByXmlIdWithCache('TEAM_EMPLOYEE')?->id;
	}

	public function getAllRoleCollectionForSync(): Item\Collection\RoleCollection
	{
		$headAndDeputyKeys = [
			'EMPLOYEE',
			'TEAM_EMPLOYEE',
			'HEAD',
			'DEPUTY_HEAD',
			'TEAM_HEAD',
			'TEAM_DEPUTY_HEAD',
		];

		$combinedXmlIdDictionary = array_merge(NodeMember::TEAM_ROLE_XML_ID, NodeMember::DEFAULT_ROLE_XML_ID);

		$xmlIds = array_values(
			array_intersect_key($combinedXmlIdDictionary, array_flip($headAndDeputyKeys))
		);

		$roleCollection = $this->roleRepository->findByXmlIds(...$xmlIds);

		foreach ($roleCollection as $role)
		{
			$this->saveToCache($role);
		}

		return $roleCollection;
	}

	public function getAccessCodeByRoleXmlId(string $xmlId): ?string
	{
		return match ($xmlId)
		{
			NodeMember::DEFAULT_ROLE_XML_ID['HEAD'] => AccessCode::ACCESS_DIRECTOR,
			NodeMember::DEFAULT_ROLE_XML_ID['DEPUTY_HEAD'] => AccessCode::ACCESS_DEPUTY,
			NodeMember::DEFAULT_ROLE_XML_ID['EMPLOYEE'] => AccessCode::ACCESS_EMPLOYEE,
			NodeMember::TEAM_ROLE_XML_ID['TEAM_HEAD'] => AccessCode::ACCESS_TEAM_DIRECTOR,
			NodeMember::TEAM_ROLE_XML_ID['TEAM_DEPUTY_HEAD'] => AccessCode::ACCESS_TEAM_DEPUTY,
			NodeMember::TEAM_ROLE_XML_ID['TEAM_EMPLOYEE'] => AccessCode::ACCESS_TEAM_EMPLOYEE,
			default => null
		};
	}

	private function getRoleByXmlIdWithCache(string $xmlIdKey): ?Item\Role
	{
		$combinedXmlIdDictionary = array_merge(NodeMember::TEAM_ROLE_XML_ID, NodeMember::DEFAULT_ROLE_XML_ID);
		$xmlId = $combinedXmlIdDictionary[$xmlIdKey] ?? null;
		if (!$xmlId)
		{
			return null;
		}

		if (empty(self::$roleByXmlIdCache[$xmlId]))
		{
			$role = $this->roleRepository->findByXmlId($xmlId);
			if (!$role)
			{
				return null;
			}

			self::saveToCache($role);
		}

		// Return clone so cached instance will not be modified no matter what
		$value = self::$roleByXmlIdCache[$xmlId] ?? null;
		if (is_object($value))
		{
			return clone $value;
		}

		return $value;
	}

	private function saveToCache(Item\Role $role): void
	{
		self::$roleByIdCache[$role->id] = $role;
		self::$roleByXmlIdCache[$role->xmlId] = $role;
	}
}
