<?php

namespace Bitrix\HumanResources\Service\Access;

use Bitrix\HumanResources\Access\Role\RoleDictionary;
use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\Access\Permission\PermissionDictionary as PermissionDictionaryAlias;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\UI\AccessRights\DataProvider;

use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Access\SectionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Role;
use Bitrix\HumanResources\Item\Collection\Access\PermissionCollection;
use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Item;

class RolePermissionService implements \Bitrix\HumanResources\Contract\Service\Access\RolePermissionService
{
	private const DB_ERROR_KEY = "HUMAN_RESOURCES_CONFIG_PERMISSIONS_DB_ERROR";

	private ?Contract\Service\Access\RoleRelationService $roleRelationService;
	private ?Contract\Repository\Access\PermissionRepository $permissionRepository;
	private ?Contract\Repository\Access\RoleRepository $roleRepository;

	public function __construct(
		?Contract\Service\Access\RoleRelationService $roleRelationService = null,
		?Contract\Repository\Access\PermissionRepository $permissionRepository = null,
		?Contract\Repository\Access\RoleRepository $roleRepository = null,
	)
	{
		$this->roleRelationService = $roleRelationService ?? Container::getAccessRoleRelationService();
		$this->permissionRepository = $permissionRepository ?? Container::getAccessPermissionRepository();
		$this->roleRepository =  $roleRepository ?? Container::getAccessRoleRepository();
	}

	/**
	 * @param array<array{
	 *     id: int|string,
	 *     title: string,
	 *     type: string,
	 *     accessRights: array<array{id: string, value: string}> }> $permissionSettings
	 * @return void
	 * @throws SqlQueryException
	 */
	public function saveRolePermissions(array &$permissionSettings): void
	{
		$roleIds = [];
		$permissionCollection = new PermissionCollection();

		foreach ($permissionSettings as &$setting)
		{
			$roleId = (int)$setting['id'];
			$roleTitle = $setting['title'];

			$roleId = $this->saveRole($roleTitle, $roleId);
			$setting['id'] = $roleId;
			$roleIds[] = $roleId;

			if(!isset($setting['accessRights']))
			{
				continue;
			}

			foreach ($setting['accessRights'] as $permission)
			{
				$permissionCollection->add(
					new Item\Access\Permission(
						roleId: $roleId,
						permissionId: $permission['id'],
						value: (int)$permission['value'],
					)
				);
			}
		}

		if(!$permissionCollection->empty())
		{
			try
			{
				$this->permissionRepository->deleteByRoleIds($roleIds);
				$this->permissionRepository->createByCollection($permissionCollection);
				if (\Bitrix\Main\Loader::includeModule("intranet"))
				{
					\CIntranetUtils::clearMenuCache();
				}
			} catch (\Exception $e)
			{
				throw new SqlQueryException(self::DB_ERROR_KEY);
			}
		}

		Container::getCacheManager()->clean(Contract\Repository\NodeRepository::NODE_ENTITY_RESTRICTION_CACHE);
	}

	public function deleteRole(int $roleId): void
	{
		try
		{
			$this->permissionRepository->deleteByRoleIds([$roleId]);
			$this->roleRelationService->deleteRelationsByRoleId($roleId);
		}
		catch (\Exception $e)
		{
			throw new SqlQueryException(self::DB_ERROR_KEY);
		}

		$result = $this->roleRepository->delete($roleId);

		if (!$result->isSuccess())
		{
			throw new SqlQueryException(Loc::getMessage(self::DB_ERROR_KEY) ?? '');
		}
	}

	/**
	 * @param string $name
	 * @param int|null $roleId
	 *
	 * @return int
	 * @throws SqlQueryException
	 */
	public function saveRole(string $name, int $roleId = null): int
	{
		$name = Encoding::convertEncodingToCurrent($name);
		try
		{
			if ($roleId > 0)
			{
				$roleUtil = new Role\RoleUtil($roleId);
				try
				{
					$roleUtil->updateTitle($name);
				}
				catch (\Exception $e)
				{
					throw new SqlQueryException(self::DB_ERROR_KEY);
				}

				return $roleId;
			}

			$role = $this->roleRepository->getRoleObjectByName($name);

			if(!$role)
			{
				$role = $this->roleRepository->create($name);
			}

			return $role->getId();
		}
		catch (\Exception $e)
		{
			throw new SqlQueryException(self::DB_ERROR_KEY);
		}
	}

	public function getRoleList(): array
	{
		return $this->roleRepository->getRoleList();
	}

	public function getUserGroups(): array
	{
		$res = $this->getRoleList();
		$roles = [];
		foreach ($res as $row)
		{
			$roles[] = [
				'id' => (int)$row['ID'],
				'title' => RoleDictionary::getRoleName($row['NAME']),
				'accessRights' => $this->getRoleAccessRights((int)$row['ID']),
				'members' => $this->getRoleMembers((int)$row['ID'])
			];
		}

		return $roles;
	}

	public function getRoleAccessRights(int $roleId): array
	{
		$settings = $this->getSettings();

		$accessRights = [];
		if (array_key_exists($roleId, $settings))
		{
			foreach ($settings[$roleId] as $permissionId => $permissionValue)
			{
				$accessRights[] = [
					'id' => $permissionId,
					'value' => $permissionValue,
				];
			}
		}

		return $accessRights;
	}

	public function getAccessRights(): array
	{
		$sections = SectionDictionary::getMap();

		$res = [];

		foreach ($sections as $sectionId => $permissions)
		{

			$rights = [];
			foreach ($permissions as $permissionId)
			{
				$permissionType = PermissionDictionary::getType($permissionId);
				$rights[] = [
					'id' => $permissionId,
					'type' => $permissionType,
					'title' => PermissionDictionary::getTitle($permissionId),
					'hint' => PermissionDictionary::getHint($permissionId),
					'variables' => $permissionType === PermissionDictionaryAlias::TYPE_VARIABLES
						? PermissionDictionary::getVariables()
						: []
					,
				];
			}
			$res[] = [
				'sectionTitle' => SectionDictionary::getTitle($sectionId),
				'rights' => $rights
			];
		}

		return $res;
	}

	private function getMemberInfo(string $code): array
	{
		$accessCode = new AccessCode($code);
		$member = (new DataProvider())->getEntity($accessCode->getEntityType(), $accessCode->getEntityId());
		return $member->getMetaData();
	}

	private function getRoleMembers(int $roleId): array
	{
		$members = [];

		$relations = $this
			->roleRelationService
			->getRelationList(["filter" =>["=ROLE_ID" => $roleId]])
		;

		foreach ($relations as $row)
		{
			$accessCode = $row['RELATION'];
			$members[$accessCode] = $this->getMemberInfo($accessCode);
		}

		return $members;
	}

	private function getSettings()
	{
		$settings = [];
		$permissionCollection = $this->permissionRepository->getPermissionList();

		foreach ($permissionCollection as $permission)
		{
			$settings[$permission->roleId][$permission->permissionId] = $permission->value;
		}
		return $settings;
	}
}