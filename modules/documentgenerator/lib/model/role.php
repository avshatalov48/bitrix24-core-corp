<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\Documentgenerator\UserPermissions;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class Role extends EO_Role
{
	protected $permissions;

	/**
	 * @return array
	 */
	public function getPermissions()
	{
		if($this->permissions === null)
		{
			$this->permissions = [];
			if($this->getId() > 0)
			{
				$permissionList = RolePermissionTable::getList(['filter' => ['ROLE_ID' => $this->getId()]]);
				while($permission = $permissionList->fetch())
				{
					$this->permissions[$permission['ENTITY']][$permission['ACTION']] = $permission['PERMISSION'];
				}
			}
			$this->permissions = $this->normalizePermissions($this->permissions);
		}

		return $this->permissions;
	}

	/**
	 * @param array $permissions
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function setPermissions(array $permissions)
	{
		$result = new Result();
		$roleId = $this->getId();
		if(!$roleId)
		{
			return $result->addError(new Error('Cannot set permissions on a role without id'));
		}

		$deletePermissionResult = RolePermissionTable::deleteByRoleId($roleId);
		if(!$deletePermissionResult->isSuccess())
		{
			$result->addErrors($deletePermissionResult->getErrors());
		}
		$permissions = $this->normalizePermissions($permissions);
		foreach ($permissions as $entity => $actions)
		{
			foreach ($actions as $action => $permission)
			{
				$addRolePermissionResult = RolePermissionTable::add(array(
					'ROLE_ID' => $roleId,
					'ENTITY' => $entity,
					'ACTION' => $action,
					'PERMISSION' => $permission
				));
				if(!$addRolePermissionResult->isSuccess())
				{
					$result->addErrors($addRolePermissionResult->getErrors());
				}
			}
		}

		if($result->isSuccess())
		{
			$this->permissions = $permissions;
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		Loc::loadLanguageFile(__FILE__);
		$name = parent::getName();
		$langName = Loc::getMessage('DOCGEN_ROLE_NAME_'.$name);
		if($langName)
		{
			return $langName;
		}

		return $name;
	}

	/**
	 * @param array $permissions
	 * @return array
	 */
	protected function normalizePermissions(array $permissions)
	{
		$map = UserPermissions::getMap();
		$result = array();

		foreach($map as $entity => $actions)
		{
			foreach($actions as $action => $permission)
			{
				if(isset($permissions[$entity][$action]))
				{
					$result[$entity][$action] = $permissions[$entity][$action];
				}
				else
				{
					$result[$entity][$action] = UserPermissions::PERMISSION_NONE;
				}
			}
		}

		return $result;
	}
}