<?php

namespace Bitrix\DocumentGenerator\Controller;

use Bitrix\DocumentGenerator\Engine\CheckPermissionsFeature;
use Bitrix\DocumentGenerator\Engine\CheckPermissions;
use Bitrix\DocumentGenerator\Model\RoleAccessTable;
use Bitrix\DocumentGenerator\Model\RoleTable;
use Bitrix\DocumentGenerator\UserPermissions;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\UI\PageNavigation;

class Role extends Base
{
	/**
	 * @return array
	 */
	public function getDefaultPreFilters()
	{
		$filters = parent::getDefaultPreFilters();
		$filters[] = new CheckPermissionsFeature();
		$filters[] = new CheckPermissions(UserPermissions::ENTITY_SETTINGS);

		return $filters;
	}

	/**
	 * @param \Bitrix\DocumentGenerator\Model\Role $role
	 * @return array
	 */
	public function getAction(\Bitrix\DocumentGenerator\Model\Role $role)
	{
		$data = $role->collectValues();
		$data['NAME'] = $role->getName();
		$data['PERMISSIONS'] = $role->getPermissions();
		return ['role' => $this->convertKeysToCamelCase($data)];
	}

	/**
	 * @param PageNavigation|null $pageNavigation
	 * @return Page
	 */
	public function listAction(PageNavigation $pageNavigation = null)
	{
		$roles = [];
		$roleList = RoleTable::getList([
	        'offset' => $pageNavigation->getOffset(),
	        'limit' => $pageNavigation->getLimit(),
	    ]);
		while($role = $roleList->fetchObject())
		{
			$data = $role->collectValues();
			$data['NAME'] = $role->getName();
			$roles[] = $this->convertKeysToCamelCase($data);
		}
		return new Page('roles', $roles, function() use ($roles)
		{
			return count($roles);
		});
	}

	/**
	 * @param array $fields
	 * @return array|null
	 */
	public function addAction(array $fields)
	{
		$role = new \Bitrix\DocumentGenerator\Model\Role();
		$role->setName($fields['name'])->setCode($fields['code']);
		$saveResult = $role->save();
		if($saveResult->isSuccess())
		{
			if(array_key_exists('permissions', $fields))
			{
				$role->setPermissions($fields['permissions']);
			}
			return $this->getAction($role);
		}
		else
		{
			$this->errorCollection = $saveResult->getErrorCollection();
			return null;
		}
	}

	/**
	 * @param \Bitrix\DocumentGenerator\Model\Role $role
	 * @param array $fields
	 * @return null
	 */
	public function updateAction(\Bitrix\DocumentGenerator\Model\Role $role, array $fields)
	{
		if(isset($fields['name']) && $fields['name'] != $role->getName())
		{
			$role->setName($fields['name']);
		}
		if(isset($fields['code']))
		{
			$role->setCode($fields['code']);
		}
		$saveResult = $role->save();
		if($saveResult->isSuccess())
		{
			if(array_key_exists('permissions', $fields))
			{
				$role->setPermissions($fields['permissions']);
			}
			return $this->getAction($role);
		}
		else
		{
			$this->errorCollection = $saveResult->getErrorCollection();
			return null;
		}
	}

	/**
	 * @param \Bitrix\DocumentGenerator\Model\Role $role
	 * @return null
	 */
	public function deleteAction(\Bitrix\DocumentGenerator\Model\Role $role)
	{
		$role->delete();

		return null;
	}

	/**
	 * @param array $accesses
	 */
	public function fillAccessesAction(array $accesses = [])
	{
		RoleAccessTable::truncate();

		foreach($accesses as $access)
		{
			$addResult = RoleAccessTable::add([
				'ROLE_ID' => $access['roleId'],
				'ACCESS_CODE' => $access['accessCode'],
			]);
			if(!$addResult->isSuccess())
			{
				$this->errorCollection->add($addResult->getErrors());
			}
		}
	}
}