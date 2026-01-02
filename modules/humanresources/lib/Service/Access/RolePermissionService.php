<?php

namespace Bitrix\HumanResources\Service\Access;

use Bitrix\HumanResources\Access\Permission\Mapper\TeamPermissionMapper;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Access\Role;
use Bitrix\HumanResources\Access\Role\RoleDictionary;
use Bitrix\HumanResources\Access\SectionDictionary;
use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\Collection\Access\PermissionCollection;
use Bitrix\HumanResources\Model\Access\AccessPermissionTable;
use Bitrix\HumanResources\Model\Access\AccessRoleRelationTable;
use Bitrix\HumanResources\Model\Access\AccessRoleTable;
use Bitrix\HumanResources\Repository\Access\PermissionRepository;
use Bitrix\HumanResources\Repository\Access\RoleRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\Access\Permission\PermissionDictionary as PermissionDictionaryAlias;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\UI\AccessRights\DataProvider;

class RolePermissionService
{
	private const DB_ERROR_KEY = "HUMAN_RESOURCES_CONFIG_PERMISSIONS_DB_ERROR";

	private ?RoleRelationService $roleRelationService;
	private ?PermissionRepository $permissionRepository;
	private ?RoleRepository $roleRepository;
	private \Bitrix\HumanResources\Enum\Access\RoleCategory $category;

	public function __construct(
		?RoleRelationService $roleRelationService = null,
		?PermissionRepository $permissionRepository = null,
		?RoleRepository $roleRepository = null,
	)
	{
		$this->roleRelationService = $roleRelationService ?? Container::getAccessRoleRelationService();
		$this->permissionRepository = $permissionRepository ?? Container::getAccessPermissionRepository();
		$this->roleRepository =  $roleRepository ?? Container::getAccessRoleRepository();
		$this->category = \Bitrix\HumanResources\Enum\Access\RoleCategory::Department;
	}

	/**
	 * @param array<array{
	 *     id: int|string,
	 *     title: string,
	 *     type: string,
	 *     accessRights: array<array{id: string, value: string}> }> $permissionSettings
	 *
	 * @return void
	 * @throws SqlQueryException|WrongStructureItemException
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
			if (!$roleId)
			{
				throw new SqlQueryException(self::DB_ERROR_KEY);
			}

			$setting['id'] = $roleId;
			$roleIds[] = $roleId;

			if(!isset($setting['accessRights']))
			{
				continue;
			}

			$teamPermissions = [];
			foreach ($setting['accessRights'] as $permission)
			{
				if (PermissionDictionary::isTeamDependentVariablesPermission($permission['id']))
				{
					$teamPermissions[$permission['id']][] = $permission;

					continue;
				}

				$permissionCollection->add(
					new Item\Access\Permission(
						roleId: $roleId,
						permissionId: $permission['id'],
						value: (int)$permission['value'],
					)
				);
			}

			if (!empty($teamPermissions))
			{
				foreach ($teamPermissions as $permissionValues)
				{
					$teamPermissionMapper = TeamPermissionMapper::createFromArray($permissionValues);

					$permissionCollection->add(
						new Item\Access\Permission(
							roleId: $roleId,
							permissionId: $teamPermissionMapper->getTeamPermissionId(),
							value: $teamPermissionMapper->getTeamPermissionValue(),
						)
					);

					$permissionCollection->add(
						new Item\Access\Permission(
							roleId: $roleId,
							permissionId: $teamPermissionMapper->getDepartmentPermissionId(),
							value: $teamPermissionMapper->getDepartmentPermissionValue(),
						)
					);
				}

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

	/**
	 * @param array<int> $roleIds
	 */
	public function deleteRoles(array $roleIds): void
	{
		try
		{
			$this->permissionRepository->deleteByRoleIds($roleIds);
			$this->roleRelationService->deleteRelationsByRoleIds($roleIds);
			$this->roleRepository->deleteByIds($roleIds);
		}
		catch (\Exception $e)
		{
			throw new SqlQueryException(self::DB_ERROR_KEY);
		}

		AccessPermissionTable::cleanCache();
		AccessRoleRelationTable::cleanCache();
		AccessRoleTable::cleanCache();
	}

	public function deleteRole(int $roleId): void
	{
		$this->deleteRoles([$roleId]);
	}

	/**
	 * @param string $name
	 * @param int $roleId
	 *
	 * @return int
	 * @throws SqlQueryException
	 */
	public function saveRole(string $name, int $roleId = 0): int
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

			$role = $this->roleRepository->create($name, $this->category);

			return (int)$role->getId();
		}
		catch (\Exception $e)
		{
			throw new SqlQueryException(self::DB_ERROR_KEY);
		}
		finally
		{
			AccessPermissionTable::cleanCache();
			AccessRoleRelationTable::cleanCache();
			AccessRoleTable::cleanCache();
		}
	}

	public function getRoleList(): array
	{
		return $this->roleRepository->getRoleList(
			$this->category
		);
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
				$defaultPermissionId = explode('_', $permissionId, 2)[0];
				if (PermissionDictionary::isTeamDependentVariablesPermission($defaultPermissionId))
				{
					$teamAccessRights = TeamPermissionMapper::transformPermissionToAccessRights($permissionId, $permissionValue);
					$accessRights = array_merge($accessRights, $teamAccessRights);

					continue;
				}

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
		$sections = SectionDictionary::getMap($this->category);

		$res = [];

		foreach ($sections as $sectionId => $permissions)
		{
			$rights = [];
			foreach ($permissions as $permissionId)
			{
				$permissionType = PermissionDictionary::getType($permissionId);
				$right = [
					'id' => $permissionId,
					'type' => $permissionType,
					'title' => PermissionDictionary::getTitle($permissionId),
					'hint' => PermissionDictionary::getHint($permissionId),
					'variables' => $permissionType !== PermissionDictionaryAlias::TYPE_TOGGLER
						? PermissionDictionary::getVariables($permissionId)
						: []
					,
				];
				$minValue = PermissionDictionary::getMinValueByTypeOrNull($permissionType);
				$maxValue = PermissionDictionary::getMaxValueByTypeOrNull($permissionType);
				$right += PermissionVariablesDictionary::getTeamPermissionSelectedVariablesAliases();
				if ($minValue !== null)
				{
					$right['minValue'] = $minValue;
					$right['emptyValue'] = $minValue;
				}
				if ($maxValue !== null)
				{
					$right['maxValue'] = $maxValue;
				}

				$rights[] = $right;
			}
			$section = [
				'sectionTitle' => SectionDictionary::getTitle($sectionId),
				'rights' => $rights,
				'sectionCode' => "code.$sectionId",
			];
			$sectionIcon = SectionDictionary::getIcon($sectionId);
			if ($sectionIcon)
			{
				$section['sectionIcon'] = $sectionIcon;
			}

			$res[] = $section;
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

	public function setCategory(\Bitrix\HumanResources\Enum\Access\RoleCategory $category): static
	{
		$this->category = $category;

		return $this;
	}
}