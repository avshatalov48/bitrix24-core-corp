<?php

namespace Bitrix\BIConnector\Access\Install;

use Bitrix\BIConnector\Access\Permission\PermissionTable;
use Bitrix\BIConnector\Access\Role\RoleRelationTable;
use Bitrix\BIConnector\Access\Role\RoleTable;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class AccessInstaller
{
	public static function install($isNewPortal = true): Result
	{
		$result = new Result();
		$map = RoleMap::getDefaultMap();

		foreach ($map as $roleCode => $roleClass)
		{
			if (is_subclass_of($roleClass, Role\Base::class))
			{
				$role = new $roleClass(
					code: $roleCode,
					isNewPortal: $isNewPortal
				);
			}
			else
			{
				continue;
			}

			$installRoleResult = $role->install();
			if (!$installRoleResult->isSuccess())
			{
				foreach ($installRoleResult->getErrors() as $error)
				{
					$result->addError(new Error($error->getMessage(), $error->getCode(), ['ROLE_NAME' => $roleCode]));
				}
			}
		}

		return $result;
	}

	public static function reinstall()
	{
		self::clearRelations();
		self::install();
	}

	private static function clearRelations()
	{
		RoleRelationTable::deleteList(['>ID' => 0]);
		RoleTable::deleteList(['>ID' => 0]);
		PermissionTable::deleteList(['>ID' => 0]);
	}
}
