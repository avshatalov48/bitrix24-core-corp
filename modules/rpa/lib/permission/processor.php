<?php

namespace Bitrix\Rpa\Permission;

class Processor
{
	protected $currentPermissions;

	public function __construct(array $currentPermissions)
	{
		$this->currentPermissions = $currentPermissions;
	}

	public function process(array $permissions): Result
	{
		$result = new Result();

		$currentPermissions = $this->currentPermissions;
		$deletePermissions = [];
		$addPermissions = [];
		$resultPermissions = [];
		foreach($currentPermissions as $key => $currentPermission)
		{
			$isFound = false;
			foreach($permissions as $permission)
			{
				if($this->isEqualPermissions($currentPermission, $permission))
				{
					$isFound = true;
					break;
				}
			}
			if(!$isFound)
			{
				$deletePermissions[] = $currentPermission;
			}
			else
			{
				$resultPermissions[] = $currentPermission;
			}
		}

		foreach($permissions as $permission)
		{
			$isFound = false;
			foreach($resultPermissions as $skipPermission)
			{
				if($this->isEqualPermissions($permission, $skipPermission))
				{
					$isFound = true;
					break;
				}
			}
			if(!$isFound)
			{
				$addPermissions[] = $permission;
				$resultPermissions[] = $permission;
			}
		}

		$result->setAddPermissions($addPermissions)->setDeletePermission($deletePermissions)->setResultPermissions($resultPermissions);

		return $result;
	}

	protected function isEqualPermissions(array $currentPermission, array $permission): bool
	{
		return (
			isset($currentPermission['ACCESS_CODE']) && isset($permission['ACCESS_CODE']) && $currentPermission['ACCESS_CODE'] === $permission['ACCESS_CODE'] &&
			isset($currentPermission['PERMISSION']) && isset($permission['PERMISSION']) && $currentPermission['PERMISSION'] === $permission['PERMISSION'] &&
			isset($currentPermission['ACTION']) && isset($permission['ACTION']) && $currentPermission['ACTION'] === $permission['ACTION']
		);
	}
}