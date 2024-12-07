<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Commands;


use Bitrix\Crm\Security\Role\Manage\DTO\PermissionModel;
use Bitrix\Crm\Security\Role\Manage\RoleManagementModelBuilder;
use Bitrix\Crm\Security\Role\Repositories\PermissionRepository;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Commands\DTO\AccessCodeDTO;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Commands\DTO\AccessRightDTO;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Commands\DTO\UserGroupsData;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Utils\PermCodeTransformer;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Utils\ValueNormalizer;
use Bitrix\Crm\Security\Role\Utils\RoleManagerUtils;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Access\Permission\PermissionDictionary;
use Bitrix\Main\Result;

class UpdateRoleCommand
{
	use Singleton;

	private PermissionRepository $permissionRepository;

	private ValueNormalizer $valueNormalizer;

	private RoleManagerUtils $utils;

	private function __construct()
	{
		$this->permissionRepository = PermissionRepository::getInstance();
		$this->valueNormalizer = ValueNormalizer::getInstance();
		$this->utils = RoleManagerUtils::getInstance();
	}

	/**
	 * @param UserGroupsData[] $userGroups
	 * @return Result
	 */
	public function execute(array $userGroups): Result
	{
		$relationSaveData = [];

		$result = new Result();

		foreach ($userGroups as $userGroup)
		{
			$roleId = $this->saveRoleName($userGroup);

			$saveRes = $this->saveRolePerms($roleId, $userGroup->accessRights);
			if (!$saveRes->isSuccess())
			{
				$result->addErrors($saveRes->getErrors());
			}

			$this->collectRelationToSave($userGroup->accessCodes, $roleId, $relationSaveData);
		}

		$this->permissionRepository->saveRolesRelations($relationSaveData);

		$this->utils->saleUpdateShopAccess();
		$this->utils->clearRolesCache();

		return $result;
	}

	/**
	 * @param int $roleId
	 * @param AccessRightDTO[] $accessRights
	 * @return Result
	 */
	private function saveRolePerms(int $roleId, array $accessRights): Result
	{
		$permissionModels = $this->uiAccessRightsToPermissionsModel($accessRights);
		[$toChange, $toRemove] = $this->dividePermissionModels($permissionModels);

		return $this->permissionRepository->applyRolePermissionData($roleId, $toRemove, $toChange);
	}

	/**
	 * @param UserGroupsData $userGroup
	 * @return int role id
	 */
	private function saveRoleName(UserGroupsData $userGroup): int
	{
		return $this->permissionRepository->updateOrCreateRole($userGroup->id, $userGroup->title);
	}

	/**
	 * @param AccessCodeDTO[] $accessCodes
	 * @param int $roleId
	 * @param $result array<string, int[]> member code as a key and array of role id as a value
	 */
	private function collectRelationToSave(array $accessCodes, int $roleId, array &$result): void
	{
		foreach ($accessCodes as $code)
		{
			if(!isset($relations[$code->id]))
			{
				$relations[$code->id] = [];
			}

			$result[$code->id][] = $roleId;
		}
	}

	/**
	 * @param AccessRightDTO[] $accessRights
	 * @return PermissionModel[]
	 */
	private function uiAccessRightsToPermissionsModel(array $accessRights): array
	{
		$permissions = [];

		foreach ($accessRights as $right)
		{
			$uiRightId = $right->id;

			$codeDTO = PermCodeTransformer::getInstance()->decodeAccessRightCode($uiRightId);

			$controlType = RoleManagementModelBuilder::getControlTypeByPermType($codeDTO->permCode);
			$value = $this->valueNormalizer->fromUIToPerms($right->value, $controlType);

			if (
				isset($permissions[$uiRightId])
				&& is_array($permissions[$uiRightId]['settings'])
			)
			{
				$permissions[$uiRightId]['settings'][] = $value;
				continue;
			}

			$permissions[$uiRightId] = [
				'permissionCode' => $codeDTO->permCode,
				'entityCode' => $codeDTO->entityCode,
				'stageField' => $codeDTO->field,
				'stageCode' => $codeDTO->fieldValue,
				'value' => null,
				'settings' => []
			];

			if ($controlType === PermissionDictionary::TYPE_MULTIVARIABLES)
			{
				if ($value !== null)
				{
					$permissions[$uiRightId]['settings'][] = $value;
				}
			}
			else
			{
				$permissions[$uiRightId]['value'] = $value;
			}
		}

		return array_values($permissions);
	}

	/**
	 * @param PermissionModel[] $permissions
	 * @return array{PermissionModel[], PermissionModel[]}
	 */
	private function dividePermissionModels(array $permissions): array
	{
		$toRemove = [];
		$toChange = [];
		foreach ($permissions as $permission) {

			$model = new PermissionModel(
				$permission['entityCode'],
				$permission['permissionCode'],
				$permission['stageField'],
				$permission['stageCode'],
				$permission['value'],
				$permission['settings'],
			);

			if (empty($permission['settings']) && empty($permission['value']))
			{
				$toRemove[] = $model;
			}
			else
			{
				$toChange[] = $model;
			}
		}

		return [$toChange, $toRemove];
	}
}