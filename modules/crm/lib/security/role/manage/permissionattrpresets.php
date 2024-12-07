<?php

namespace Bitrix\Crm\Security\Role\Manage;

use Bitrix\Crm\Security\Role\Manage\Permissions\Add;
use Bitrix\Crm\Security\Role\Manage\Permissions\MyCardView;
use Bitrix\Crm\Security\Role\Manage\Permissions\Automation;
use Bitrix\Crm\Security\Role\Manage\Permissions\Delete;
use Bitrix\Crm\Security\Role\Manage\Permissions\Export;
use Bitrix\Crm\Security\Role\Manage\Permissions\HideSum;
use Bitrix\Crm\Security\Role\Manage\Permissions\Import;
use Bitrix\Crm\Security\Role\Manage\Permissions\Permission;
use Bitrix\Crm\Security\Role\Manage\Permissions\Read;
use Bitrix\Crm\Security\Role\Manage\Permissions\Transition;
use Bitrix\Crm\Security\Role\Manage\Permissions\Write;

class PermissionAttrPresets
{
	/**
	 * @return Permission[]
	 */
	public static function crmEntityPreset(): array
	{
		return [
			new Read(self::userHierarchyAndOpen()),
			new Add(self::userHierarchyAndOpen()),
			new Write(self::userHierarchyAndOpen()),
			new Delete(self::userHierarchyAndOpen()),
			new Export(self::userHierarchyAndOpen()),
			new Import(self::userHierarchyAndOpen()),
			new MyCardView(self::allowedYesNo()),
		];
	}

	public static function crmEntityPresetAutomation(): array
	{
		return array_merge(
			self::crmEntityPreset(),
			[
				new Automation(self::readWrite()),
			]
		);
	}

	public static function crmEntityKanbanHideSum(): array
	{
		return [
			new HideSum(self::hideSum()),
		];
	}

	public static function crmStageTransition(array $stages = []): array
	{
		$variants = [
			'INHERIT' => GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_TRANSITION_INHERITED'),
			'ANY' => GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_TRANSITION_ANY')
		] + $stages;

		return [
			new Transition($variants),
		];
	}

	public static function userHierarchy(): array
	{
		return [
			'' => GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_'),
			BX_CRM_PERM_SELF => GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_A'),
			BX_CRM_PERM_DEPARTMENT => GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_D'),
			BX_CRM_PERM_SUBDEPARTMENT => GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_F'),
			BX_CRM_PERM_ALL => GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_X'),
		];
	}

	public static function userHierarchyAndOpen(): array
	{
		return [
			'' => GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_'),
			BX_CRM_PERM_SELF => GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_A'),
			BX_CRM_PERM_DEPARTMENT => GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_D'),
			BX_CRM_PERM_SUBDEPARTMENT => GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_F'),
			BX_CRM_PERM_OPEN => GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_O'),
			BX_CRM_PERM_ALL => GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_X'),
		];
	}

	public static function switchAll(): array
	{
		return [
			'' => GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_'),
			BX_CRM_PERM_ALL => GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_X'),
		];
	}

	private static function hideSum(): array
	{
		return [
			'' => GetMessage('CRM_SECURITY_ROLE_PERMS_HIDE_SUM'),
			BX_CRM_PERM_ALL => GetMessage('CRM_SECURITY_ROLE_PERMS_SHOW_SUM'),
		];
	}

	public static function readWrite(): array
	{
		return [
			'' => GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_AUTOMATION_NONE'),
			BX_CRM_PERM_ALL => GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_AUTOMATION_ALL'),
		];
	}

	public static function allowedYesNo(): array
	{
		return [
			'' => GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_ALLOWED_NO'),
			BX_CRM_PERM_ALL => GetMessage('CRM_SECURITY_ROLE_PERMS_TYPE_ALLOWED_YES'),
		];
	}
}