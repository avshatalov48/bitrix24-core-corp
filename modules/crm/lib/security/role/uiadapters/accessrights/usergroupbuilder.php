<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights;


use Bitrix\Crm\Security\Role\Manage\RoleManagementModelBuilder;
use Bitrix\Crm\Security\Role\Repositories\PermissionRepository;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Utils\PermCodeTransformer;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Utils\ValueNormalizer;
use Bitrix\Crm\Security\Role\Utils\RoleManagerUtils;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Traits\Singleton;

class UserGroupBuilder
{
	use Singleton;

	private PermissionRepository $permissionRepository;

	private ValueNormalizer $valueNormalizer;

	private RoleManagerUtils $utils;

	public function __construct()
	{
		$this->permissionRepository = PermissionRepository::getInstance();
		$this->utils = RoleManagerUtils::getInstance();
		$this->valueNormalizer = ValueNormalizer::getInstance();
	}

	public function build(): array
	{
		$roles = $this->permissionRepository->getAllRoles();

		$rolesIds = array_column($roles, 'ID');

		$accessRightsByRoleId = $this->collectAccessRightGroupedByRoleId($rolesIds);

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
				'accessRights' => $accessRightsByRoleId[$roleId] ?? [],
				'members' => $roleMembers
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
			$this->getUserIdsFromRoleRelations($rolesRelations)
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
			if ($providerId === 'user')
			{
				$avatar = $avatarsMap[$memberId] ?? null;
			}


			$result[$roleId][] = [
				'id' => $memberId,
				'name' => $membersInfo[$memberId]['name'] ?? '',
				'providerId' => $providerId,
				'avatar' => $avatar,
				'type' => $this->providerIdToType($providerId),
			];
		}

		return $result;
	}

	private function getUserIdsFromRoleRelations(array $relations): array
	{
		$result = [];
		foreach ($relations as $relation)
		{
			$matches = [];
			$matchRes = preg_match('/^U(\d+)$/', $relation['RELATION'], $matches);
			if ($matchRes === 1 && isset($matches[1]))
			{
				$result[] = (int)$matches[1];
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

			$result['U'.$user['ID']] = $user['PHOTO_URL'];
		}

		return $result;
	}

	private function providerIdToType(string $providerId): string
	{
		return match ($providerId) {
			'user' => 'users',
			'intranet' => 'departments',
			'socnetgroup' => 'sonetgroups',
			'group' => 'groups',
			default => '',
		};
	}

	/**
	 * @param int[] $roleIds
	 * @return array
	 */
	private function collectAccessRightGroupedByRoleId(array $roleIds): array
	{
		$perms = $this->permissionRepository->queryActualPermsByRoleIds($roleIds);

		$accessRights = [];

		foreach ($perms as $perm)
		{
			$roleId = $perm['ROLE_ID'];

			if (!isset($accessRights[$roleId]))
			{
				$accessRights[$roleId] = [];
			}

			$rightId = PermCodeTransformer::getInstance()->makeAccessRightPermCode(
				new PermIdentifier($perm['ENTITY'], $perm['PERM_TYPE'], $perm['FIELD'], $perm['FIELD_VALUE'])
			);

			$controlType = RoleManagementModelBuilder::getControlTypeByPermType($perm['PERM_TYPE']);
			$value = $this->valueNormalizer->fromPermsToUI($perm, $controlType);

			$accessRights[$roleId][] = [
				'id' => $rightId,
				'value' => $value,
			];
		}

		return $accessRights;
	}
}