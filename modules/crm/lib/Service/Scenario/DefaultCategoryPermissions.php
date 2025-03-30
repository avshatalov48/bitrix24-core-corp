<?php

namespace Bitrix\Crm\Service\Scenario;

use Bitrix\Crm\CategoryIdentifier;
use Bitrix\Crm\Feature;
use Bitrix\Crm\Security\Role\RolePreset;
use Bitrix\Crm\Service;
use Bitrix\Main\Result;
use Bitrix\Crm\Security\Role\Utils\RolePermissionLogContext;
use Bitrix\Main\Application;

class DefaultCategoryPermissions extends Service\Scenario
{
	/** @var \CCrmRole */
	protected $roleClassName = \CCrmRole::class;

	protected int $entityTypeId;
	protected int $categoryId;
	protected bool $needSetOpenPermissions;

	public function __construct(int $entityTypeId, int $categoryId, bool $needSetOpenPermissions = true)
	{
		$this->entityTypeId = $entityTypeId;
		$this->categoryId = $categoryId;
		$this->needSetOpenPermissions = $needSetOpenPermissions;
	}

	public function play(): Result
	{
		$result = new Result();

		Service\Container::getInstance()->getFactory($this->entityTypeId)?->clearCategoriesCache();

		$categoryIdentifier = new CategoryIdentifier($this->entityTypeId, $this->categoryId);
		$defaultPermissionSet =
			$this->needSetOpenPermissions
			? RolePreset::getMaxPermissionSetForEntity($categoryIdentifier)
			: RolePreset::getMinPermissionSetForEntity($categoryIdentifier)
		;

		if (empty($defaultPermissionSet))
		{
			return $result;
		}
		Application::getInstance()->addBackgroundJob([$this, 'addPermissionsForNewCategory']); // delayed because depends on binding the smart process to an automated solution

		return $result;
	}

	public function addPermissionsForNewCategory(): void
	{
		$permissionEntity = Service\UserPermissions::getPermissionEntityType(
			$this->entityTypeId,
			$this->categoryId
		);
		Service\Container::getInstance()->getFactory($this->entityTypeId)?->clearCategoriesCache();

		/** @var \CCrmRole $role */
		$role = new $this->roleClassName;
		$roleDbResult = $this->roleClassName::GetList();

		$systemRolesIds = \Bitrix\Crm\Security\Role\RolePermission::getSystemRolesIds();

		$categoryIdentifier = new CategoryIdentifier($this->entityTypeId, $this->categoryId);
		$defaultPermissionSet =
			$this->needSetOpenPermissions
				? RolePreset::getMaxPermissionSetForEntity($categoryIdentifier)
				: RolePreset::getMinPermissionSetForEntity($categoryIdentifier)
		;

		if (empty($defaultPermissionSet))
		{
			return;
		}

		RolePermissionLogContext::getInstance()->set([
			'scenario' => 'add category',
			'entityTypeId' => $this->entityTypeId,
			'categoryId' => $this->categoryId,
		]);

		$strictByRoleGroupCode =
			Feature::enabled(\Bitrix\Crm\Feature\PermissionsLayoutV2::class)
				? (string)\Bitrix\Crm\Security\Role\GroupCodeGenerator::getGroupCodeByEntityTypeId($this->entityTypeId)
				: null
		;

		while($roleFields = $roleDbResult->Fetch())
		{
			$roleID = (int)$roleFields['ID'];
			$roleGroupCode = (string)$roleFields['GROUP_CODE'];
			if (in_array($roleID, $systemRolesIds, false)) // do not affect system roles
			{
				continue;
			}
			if (!is_null($strictByRoleGroupCode) && $roleGroupCode !== $strictByRoleGroupCode)
			{
				continue;
			}

			$roleRelation = \CCrmRole::getRolePermissionsAndSettings($roleID);
			if(isset($roleRelation[$permissionEntity]))
			{
				continue;
			}
			$roleRelation[$permissionEntity] = $defaultPermissionSet;

			if ($roleFields['CODE'])
			{
				$roleRelation[$permissionEntity] = RolePreset::getDefaultPermissionSetForEntityByCode(
					$roleFields['CODE'],
					$categoryIdentifier
				);
			}

			$fields = ['RELATION' => $roleRelation];
			$role->Update($roleID, $fields);
		}

		RolePermissionLogContext::getInstance()->clear();
	}
}
