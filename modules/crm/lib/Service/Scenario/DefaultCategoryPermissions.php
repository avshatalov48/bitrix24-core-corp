<?php

namespace Bitrix\Crm\Service\Scenario;

use Bitrix\Crm\Service;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

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

		$permissionEntity = Service\UserPermissions::getPermissionEntityType(
			$this->entityTypeId,
			$this->categoryId
		);
		Service\Container::getInstance()->getFactory($this->entityTypeId)?->clearCategoriesCache();

		/** @var \CCrmRole $role */
		$role = new $this->roleClassName;
		$roleDbResult = $this->roleClassName::GetList();
		$systemRolesIds = \Bitrix\Crm\Security\Role\RolePermission::getSystemRolesIds();

		$categoryIdentifier = new \Bitrix\Crm\CategoryIdentifier($this->entityTypeId, $this->categoryId);
		$defaultPermissionSet =
			$this->needSetOpenPermissions
			? \CCrmRole::getDefaultPermissionSetForEntity($categoryIdentifier)
			: \CCrmRole::getBasePermissionSetForEntity($categoryIdentifier)
		;

		if (empty($defaultPermissionSet))
		{
			return $result;
		}

		while($roleFields = $roleDbResult->Fetch())
		{
			$roleID = (int)$roleFields['ID'];
			if (in_array($roleID, $systemRolesIds, false)) // do not affect system roles
			{
				continue;
			}
			$roleRelation = \CCrmRole::getRolePermissionsAndSettings($roleID);
			if(isset($roleRelation[$permissionEntity]))
			{
				continue;
			}

			if(!isset($roleRelation[$permissionEntity]))
			{
				$roleRelation[$permissionEntity] = $defaultPermissionSet;
			}
			$fields = ['RELATION' => $roleRelation];
			$updateResult = $role->Update($roleID, $fields);
			if (!$updateResult)
			{
				if (isset($fields['RESULT_MESSAGE']))
				{
					$result->addError(new Error($fields['RESULT_MESSAGE']));
				}
				else
				{
					$result->addError(new Error('Error setting default category permissions'));
				}
			}
		}

		return $result;
	}
}
