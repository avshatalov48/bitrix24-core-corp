<?php

namespace Bitrix\Crm\Service\Scenario;

use Bitrix\Crm\Service;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class DefaultCategoryPermissions extends Service\Scenario
{
	/** @var \CCrmRole */
	protected $roleClassName = \CCrmRole::class;

	protected $entityTypeId;
	protected $categoryId;

	public function __construct(int $entityTypeId, int $categoryId)
	{
		$this->entityTypeId = $entityTypeId;
		$this->categoryId = $categoryId;
	}

	public function play(): Result
	{
		$result = new Result();

		$permissionEntity = Service\UserPermissions::getPermissionEntityType(
			$this->entityTypeId,
			$this->categoryId
		);

		/** @var \CCrmRole $role */
		$role = new $this->roleClassName;
		$roleDbResult = $this->roleClassName::GetList();
		$systemRolesIds = \Bitrix\Crm\Security\Role\RolePermission::getSystemRolesIds();
		while($roleFields = $roleDbResult->Fetch())
		{
			$roleID = (int)$roleFields['ID'];
			if (in_array($roleID, $systemRolesIds, false)) // do not affect system roles
			{
				continue;
			}
			$roleRelation = \CCrmRole::GetRolePerms($roleID);
			if(isset($roleRelation[$permissionEntity]))
			{
				continue;
			}

			if(!isset($roleRelation[$permissionEntity]))
			{
				$roleRelation[$permissionEntity] = \CCrmRole::GetDefaultPermissionSet();
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
