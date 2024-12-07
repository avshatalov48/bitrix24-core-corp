<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Access\Service\RolePermissionService;
use Bitrix\Crm\Integration\Sign\Access\Service\RoleRelationService;

\Bitrix\Main\Loader::includeModule('sign');
\Bitrix\Main\Loader::includeModule('crm');
class ConfigRoleEditAjaxController extends \Bitrix\Main\Engine\Controller
{
	public function savePermissionsAction(array $userGroups)
	{
		if(!\Bitrix\Sign\Access\AccessController::can(
			\Bitrix\Main\Engine\CurrentUser::get()->getId(),
			ActionDictionary::ACTION_ACCESS_RIGHTS
		))
		{
			return;
		}
		
		if (!is_array($userGroups) || empty($userGroups) || !check_bitrix_sessid())
		{
			return;
		}

		try
		{
			$permissionService = (new RolePermissionService());

			$permissionService
				->saveRolePermissions($userGroups);
			
			(new RoleRelationService())->saveRoleRelation($userGroups);

			return [
				'USER_GROUPS' => $permissionService->getUserGroups(),
				'ACCESS_RIGHTS' => $permissionService->getAccessRights()
			];
		}
		catch (\Exception $e)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('SIGN_CONFIG_PERMISSIONS_DB_ERROR'));
		}
	}

	public function deleteRoleAction(int $roleId)
	{
		if(!\Bitrix\Sign\Access\AccessController::can(
			\Bitrix\Main\Engine\CurrentUser::get()->getId(),
			ActionDictionary::ACTION_ACCESS_RIGHTS
		))
		{
			return;
		}

		if (!is_int($roleId) || !check_bitrix_sessid())
		{
			return;
		}

		try
		{
			(new RolePermissionService())->deleteRole($roleId);
		}
		catch (\Bitrix\Main\DB\SqlQueryException $e)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(
				Loc::getMessage('SIGN_CONFIG_ROLE_DELETE_DB_ERROR')
			);
		}
	}

	/**
	 *
	 * @return array
	 */
	public function loadAction()
	{
		if(!\Bitrix\Sign\Access\AccessController::can(
			\Bitrix\Main\Engine\CurrentUser::get()->getId(),
			ActionDictionary::ACTION_ACCESS_RIGHTS
		))
		{
			return [];
		}
		
		$permissionService = new RolePermissionService();

		return [
			'USER_GROUPS' => $permissionService->getUserGroups(),
			'ACCESS_RIGHTS' => $permissionService->getAccessRights()
		];
	}
}