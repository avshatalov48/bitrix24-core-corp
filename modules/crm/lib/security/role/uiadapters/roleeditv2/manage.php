<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\RoleEditV2;

use Bitrix\Crm\Security\Role\Exceptions\RoleNotFoundException;
use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\DTO\PermissionModel;
use Bitrix\Crm\Security\Role\Manage\Entity\AutomatedSolutionConfig;
use Bitrix\Crm\Security\Role\Manage\Entity\AutomatedSolutionList;
use Bitrix\Crm\Security\Role\Manage\Entity\ButtonConfig;
use Bitrix\Crm\Security\Role\Manage\Entity\WebFormConfig;
use Bitrix\Crm\Security\Role\Manage\Permissions\HideSum;
use Bitrix\Crm\Security\Role\Manage\RoleManagementModelBuilder;
use Bitrix\Crm\Security\Role\Model\RoleTable;
use Bitrix\Crm\Security\Role\Repositories\PermissionRepository;
use Bitrix\Crm\Security\Role\Utils\RoleManagerUtils;
use Bitrix\Crm\Security\Role\Validators\RoleNameValidator;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class Manage
{
	private PermissionRepository $permissionRepository;

	private RoleManagementModelBuilder $entitiesBuilder;

	private RoleManagerUtils $utils;

	private RoleNameValidator $roleNameValidator;

	private MultivariablesCompatibilityAdapter $adapter;

	public function __construct()
	{
		$this->entitiesBuilder = RoleManagementModelBuilder::getInstance();
		$this->permissionRepository = PermissionRepository::getInstance();
		$this->utils = RoleManagerUtils::getInstance();
		$this->roleNameValidator = RoleNameValidator::getInstance();
		$this->adapter = new MultivariablesCompatibilityAdapter();
	}

	public function getRoleData(?int $roleId): RoleData
	{
		$permissionEntities = $this->excludeEntitiesIfNecessary(
			$this->entitiesBuilder->buildModels()
		);

		if ($roleId > 0)
		{
			$roleDto = $this->getRoleOrThrow($roleId);
			$roleAssignedPermissions = $this->permissionRepository->getRoleAssignedPermissions($roleId);
		}
		else
		{
			$roleDto = RoleDTO::createBlank();
			$roleAssignedPermissions = $this->permissionRepository->getDefaultRoleAssignedPermissions($permissionEntities);
		}

		$permissionEntities = $this->adaptVariants($permissionEntities, $roleAssignedPermissions);
		$roleAssignedPermissions = $this->adapter->prepareRoleAssignedPermissions(
			$this->entitiesBuilder,
			$roleAssignedPermissions,
		);

		return new RoleData(
			$roleDto,
			$permissionEntities,
			$roleAssignedPermissions,
			$this->permissionRepository->getTariffRestrictions(),
		);
	}

	/**
	 * @param array $data
	 * @return Result
	 * @throws RoleNotFoundException
	 */
	public function save(array $data): Result
	{
		$tariffResult = $this->utils->checkTariffRestriction();
		if (!$tariffResult->isSuccess())
		{
			return $tariffResult;
		}

		$result = new Result();

		$id = (int)($data['id'] ?? 0);
		$name = $data['name'] ?? '';

		if ($id !== 0)
		{
			$this->getRoleOrThrow($id);
		}


		$validationResult = $this->roleNameValidator->validate($name, $id);
		if (!$validationResult->isSuccess())
		{
			return $validationResult;
		}

		if ($id > 0)
		{
			RoleTable::update($id, ['NAME' => $name]);
		}
		else
		{
			$addResult = $this->permissionRepository->addRole($name);
			if (!$addResult->isSuccess())
			{
				$result->addError(new Error($addResult->getErrorMessages()[0] ?? ''));

				return $result;
			}
			$id = $addResult->getId();
		}

		$result->setData(['id' => $id]);

		$permissions = $data['permissions'] ?? [];
		$toRemove = PermissionModel::creteFromAppFormBatch($permissions['toRemove'] ?? []);
		$toChange = PermissionModel::creteFromAppFormBatch($permissions['toChange'] ?? []);
		$toChange = (new \Bitrix\Crm\Security\Role\UIAdapters\RoleEditV2\MultivariablesCompatibilityAdapter())->prepareChangedValues($toChange);

		$this->utils->saleUpdateShopAccess();
		$this->utils->clearRolesCache();

		$applyResult = $this->permissionRepository->applyRolePermissionData($id, $toRemove, $toChange);
		if (!$applyResult->isSuccess())
		{
			$result->addError(new Error($applyResult->getErrorMessages()[0] ?? ''));
		}

		return $result;
	}

	public function delete(int $roleId): Result
	{
		$tariffResult = $this->utils->checkTariffRestriction();
		if (!$tariffResult->isSuccess())
		{
			return $tariffResult;
		}

		$this->permissionRepository->deleteRole($roleId);

		return new Result();
	}

	/**
	 * @param int $roleId
	 * @return RoleDTO
	 * @throws RoleNotFoundException
	 */
	private function getRoleOrThrow(int $roleId): RoleDTO
	{
		$roleDto = $this->permissionRepository->getRole($roleId);

		if ($roleDto === null)
		{
			throw new RoleNotFoundException(Loc::getMessage('CRM_SECURITY_ROLE_PERMISSION_DENIED'));
		}

		return RoleDTO::createFromDbRow($roleDto);
	}

	/**
	 * @param EntityDTO[] $entities
	 * @return array
	 */
	private function excludeEntitiesIfNecessary(array $entities): array
	{
		$excludeEntities = [
			'CONFIG',
			WebFormConfig::CODE,
			ButtonConfig::CODE,
			AutomatedSolutionList::ENTITY_CODE,
		];

		$excludeByPrefixes = [
			AutomatedSolutionConfig::ENTITY_CODE_PREFIX,
		];

		$result = [];
		foreach ($entities as $entity)
		{
			if (in_array($entity->code(), $excludeEntities, true))
			{
				continue;
			}

			foreach ($excludeByPrefixes as $prefix)
			{
				if (str_starts_with($entity->code(), $prefix))
				{
					continue 2;
				}
			}

			$result[] = $entity;
		}

		return $result;
	}

	/**
	 * @param EntityDTO[] $entities
	 * @param array[] $roleAssignedPermissions
	 *
	 * @return EntityDTO[]
	 */
	private function adaptVariants(array $entities, array $roleAssignedPermissions): array
	{
		$permissionEntities = $this->adapter->preparePermissionValues($entities, $roleAssignedPermissions);

		foreach ($permissionEntities as $entity)
		{
			foreach ($entity->permissions() as $permission)
			{
				if ($permission->code() !== 'HIDE_SUM')
				{
					continue;
				}

				// EditApp inserts 'inherit' variants to all 'hasStages' permissions manually
				$permission->variants()?->remove(HideSum::INHERIT);
			}
		}

		return $permissionEntities;
	}
}
