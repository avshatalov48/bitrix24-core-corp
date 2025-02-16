<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights;


use Bitrix\Crm\Security\Role\Manage\RoleManagementModelBuilder;
use Bitrix\Crm\Security\Role\Repositories\PermissionRepository;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Utils\PermCodeTransformer;
use Bitrix\Crm\Security\Role\Utils\RoleManagerUtils;
use Bitrix\Crm\Service\Container;

final class UserGroupBuilder
{
	private PermissionRepository $permissionRepository;

	private ?array $targetAccessRightCodes = null;
	private bool $isExcludeRolesWithoutRights = false;
	private ?string $includeRolesWithoutRightsForGroupCode = null;

	public function __construct()
	{
		$this->permissionRepository = PermissionRepository::getInstance();
		$this->utils = RoleManagerUtils::getInstance();
	}

	/**
	 * @param array[] $accessRights
	 * @return $this
	 */
	public function isFilterByAccessRightsCodes(array $accessRights): self
	{
		$this->targetAccessRightCodes = [];
		foreach ($accessRights as $accessRight)
		{
			$ids = array_column($accessRight['rights'] ?? [], 'id');
			array_push($this->targetAccessRightCodes, ...$ids);
		}

		return $this;
	}

	public function isExcludeRolesWithoutRights(bool $isExclude = true): self
	{
		$this->isExcludeRolesWithoutRights = $isExclude;

		return $this;
	}

	public function includeRolesWithoutRightsForGroupCode(string $groupCode): self
	{
		$this->includeRolesWithoutRightsForGroupCode = $groupCode;

		return $this;
	}

	public function build(): array
	{
		$roles = $this->permissionRepository->getAllRoles();
		[$roles, $accessRightValuesByRoleId] = $this->collectAccessRightValuesGroupedByRoleId($roles);

		$rolesIds = array_column($roles, 'ID');
		$allMembers = $this->collectMembers($rolesIds);

		$result = [];
		foreach ($roles as $role)
		{
			$roleId = $role['ID'];
			$roleMembers = [];
			foreach (($allMembers[$roleId] ?? []) as $member)
			{
				$roleMembers[$member['id']] = [
					'type' => $member['type'],
					'id' => $member['id'],
					'name' => $member['name'],
					'url' => '',
					'avatar' => $member['avatar'],
				];
			}

			$result[] = [
				'id' => (int)$role['ID'],
				'title' => $role['NAME'],
				'accessRights' => $accessRightValuesByRoleId[$roleId] ?? [],
				'members' => $roleMembers,
			];
		}

		return $result;
	}

	private function collectMembers(array $roleIds): array
	{
		$rolesRelations = $this->permissionRepository->queryRolesRelations($roleIds);
		$allAccessCodes = array_unique(array_column($rolesRelations, 'RELATION'));

		$CAccess = new \CAccess();
		$membersInfo = $CAccess->GetNames($allAccessCodes);

		$result = [];

		$avatarsMap = $this->queryUserAvatarsMap(
			$this->getUserIdsFromRoleRelations($rolesRelations),
		);

		foreach ($rolesRelations as $rel) {
			$roleId = $rel['ROLE_ID'];
			if (!isset($result[$roleId])) {
				$result[$roleId] = [];
			}

			$memberId = $rel['RELATION'];
			$providerId = $membersInfo[$memberId]['provider_id'] ?? null;

			if ($providerId === null)
			{
				continue;
			}

			$avatar = '';
			$memberType = $this->getMemberType($providerId, $memberId);
			if ($memberType === 'users')
			{
				$avatar = $avatarsMap[$this->extractUserId($memberId)] ?? null;
			}

			$result[$roleId][] = [
				'id' => $memberId,
				'name' => $membersInfo[$memberId]['name'] ?? '',
				'providerId' => $providerId,
				'avatar' => $avatar,
				'type' => $memberType,
			];
		}

		return $result;
	}

	private function getUserIdsFromRoleRelations(array $relations): array
	{
		$result = [];
		foreach ($relations as $relation)
		{
			$userId = $this->extractUserId($relation['RELATION']);
			if ($userId !== null)
			{
				$result[] = $userId;
			}
		}

		return array_unique($result);
	}

	private function queryUserAvatarsMap(array $userIds): array
	{
		if (empty($userIds))
		{
			return [];
		}

		$usersData = Container::getInstance()->getUserBroker()->getBunchByIds($userIds);

		$result = [];
		foreach ($usersData as $user)
		{
			if (empty($user['PHOTO_URL']))
			{
				continue;
			}

			$result[$user['ID']] = $user['PHOTO_URL'];
		}

		return $result;
	}

	private function getMemberType(string $providerId, string $accessCode): string
	{
		if ($this->extractUserId($accessCode) !== null)
		{
			return 'users';
		}

		return match ($providerId) {
			'user' => 'users',
			'intranet' => 'departments',
			'socnetgroup' => 'sonetgroups',
			'group' => 'groups',
			default => '',
		};
	}

	private function extractUserId(string $accessCode): ?int
	{
		if (!preg_match('#^I?U(\d+)$#', $accessCode, $matches))
		{
			return null;
		}

		if (!isset($matches[1]))
		{
			return null;
		}

		return (int)$matches[1];
	}

	/**
	 * @param array $roles
	 *
	 * @return array
	 */
	private function collectAccessRightValuesGroupedByRoleId(array $roles): array
	{
		$roleIds = array_column($roles, 'ID');
		if (empty($roleIds))
		{
			return [[], []];
		}

		$perms = $this->permissionRepository->queryActualPermsByRoleIds($roleIds);

		$perms = $this->filterByAccessRightsCodes($perms);
		$roles = $this->excludeRolesWithoutRights($roles, $perms);

		$values = [];

		foreach ($perms as $perm)
		{
			[$roleId, $accessRightValues] = $this->collectAccessRightValuesByPermRow($perm);

			$currentValues = $values[$roleId] ?? [];
			$values[$roleId] = array_merge($currentValues, $accessRightValues);
		}

		return [$roles, $values];
	}

	private function collectAccessRightValuesByPermRow(array $perm): array
	{
		$permIdentifier = new PermIdentifier($perm['ENTITY'], $perm['PERM_TYPE'], $perm['FIELD'], $perm['FIELD_VALUE']);
		$rightId = PermCodeTransformer::getInstance()->makeAccessRightPermCode($permIdentifier);

		$value = RoleManagementModelBuilder::getInstance()
			->getPermissionByCode($permIdentifier->entityCode, $permIdentifier->permCode)
			?->getControlMapper()
			->getValueForUi($perm['ATTR'], $perm['SETTINGS'])
			?? $perm['ATTR'] ?? ''
		;

		$values = [];
		foreach ((array)$value as $singleValue)
		{
			$values[] = [
				'id' => $rightId,
				'value' => $singleValue,
			];
		}

		return [$perm['ROLE_ID'], $values];
	}

	protected function filterByAccessRightsCodes(array $permissions): array
	{
		if ($this->targetAccessRightCodes === null)
		{
			return $permissions;
		}

		$result = [];

		foreach ($permissions as $permission)
		{
			$identifier = PermIdentifier::fromArray($permission);
			$code = PermCodeTransformer::getInstance()->makeAccessRightPermCode($identifier);

			if (in_array($code, $this->targetAccessRightCodes, true))
			{
				$result[] = $permission;
			}
		}

		return $result;
	}

	private function excludeRolesWithoutRights(array $roles, array $permissions): array
	{
		if (
			!$this->isExcludeRolesWithoutRights
			&& is_null($this->includeRolesWithoutRightsForGroupCode)
		)
		{
			return $roles;
		}

		$roleIds = [];
		$roleIdsForGroupCode = [];

		foreach ($roles as $role)
		{
			$roleGroupCode = (string)$role['GROUP_CODE'];
			$roleId = (int)$role['ID'];
			if ($roleGroupCode === $this->includeRolesWithoutRightsForGroupCode)
			{
				$roleIdsForGroupCode[] = $roleId;
			}
		}
		if (!is_null($this->includeRolesWithoutRightsForGroupCode))
		{
			$roleIds = $roleIdsForGroupCode;
		}

		foreach ($permissions as $permission)
		{
			$roleId = (int)$permission['ROLE_ID'];
			if (in_array($roleId, $roleIds, true))
			{
				continue;
			}

			$identifier = PermIdentifier::fromArray($permission);
			$permissionEntity = RoleManagementModelBuilder::getInstance()->getPermissionByCode(
				$identifier->entityCode,
				$identifier->permCode,
			);

			if ($permissionEntity === null)
			{
				continue;
			}

			$isEmpty = \Bitrix\Crm\Security\Role\Utils\RolePermissionChecker::isPermissionEmpty(
				\Bitrix\Crm\Security\Role\Manage\DTO\PermissionModel::createFromDbArray($permission)
			);

			if ($isEmpty)
			{
				if (is_null($this->includeRolesWithoutRightsForGroupCode))
				{
					continue;
				}
				elseif (!in_array($roleId, $roleIdsForGroupCode, true)) // skip only empty roles not in $this->includeRolesWithoutRightsForGroupCode
				{
					continue;
				}
			}

			$roleIds[] = $roleId;
		}

		$isRoleInRoleIds = static fn (array $role): bool => in_array((int)$role['ID'], $roleIds, true);

		return array_filter($roles, $isRoleInRoleIds);
	}
}
