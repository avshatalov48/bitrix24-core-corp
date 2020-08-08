<?php
namespace Bitrix\Tasks\Access\Component;

use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class ConfigPermissions
{
	private const
		SECTION_TASKS 		= 'SECTION_TASKS',
		SECTION_TEMPLATES 	= 'SECTION_TEMPLATES',
		SECTION_ROBOTS 		= 'SECTION_ROBOTS',
		SECTION_ACCESS		= 'SECTION_ACCESS';

	private const PREVIEW_LIMIT = 0;

	public function getAccessRights()
	{
		$sections = $this->getSections();

		$res = [];

		foreach ($sections as $sectionName => $permissions)
		{
			$rights = [];
			foreach ($permissions as $permissionId)
			{
				$rights[] = PermissionDictionary::getPermission($permissionId);
			}
			$res[] = [
				'sectionTitle' => Loc::getMessage('TASKS_CONFIG_PERMISSIONS_' . $sectionName) ?? $sectionName,
				'rights' => $rights
			];
		}

		return $res;
	}

	public function getUserGroups(): array
	{
		$list = \Bitrix\Tasks\Access\Role\RoleUtil::getRoles();

		$roles = [];
		foreach ($list as $row)
		{
			$roleId = (int) $row['ID'];

			$roles[] = [
				'id' 			=> $roleId,
				'title' 		=> \Bitrix\Tasks\Access\Role\RoleDictionary::getRoleName($row['NAME']),
				'accessRights' 	=> $this->getRoleAccessRights($roleId),
				'members' 		=> $this->getRoleMembers($roleId)
			];
		}

		return $roles;
	}

	private function getSections(): array
	{
		return [
			self::SECTION_TASKS => [
				PermissionDictionary::TASK_RESPONSE_EDIT,
				PermissionDictionary::TASK_RESPONSE_DELEGATE,
				PermissionDictionary::TASK_RESPONSE_ASSIGN,
				PermissionDictionary::TASK_RESPONSE_CHANGE_RESPONSIBLE,
				PermissionDictionary::TASK_RESPONSE_CHECKLIST_EDIT,
				PermissionDictionary::TASK_RESPONSE_CHECKLIST_ADD,
				PermissionDictionary::TASK_CLOSED_DIRECTOR_EDIT,
				PermissionDictionary::TASK_DIRECTOR_DELETE,

				PermissionDictionary::TASK_DEPARTMENT_DIRECT,
				PermissionDictionary::TASK_DEPARTMENT_MANAGER_DIRECT,
				PermissionDictionary::TASK_DEPARTMENT_VIEW,
				PermissionDictionary::TASK_DEPARTMENT_EDIT,
				PermissionDictionary::TASK_CLOSED_DEPARTMENT_EDIT,
				PermissionDictionary::TASK_DEPARTMENT_DELETE,

				PermissionDictionary::TASK_NON_DEPARTMENT_MANAGER_DIRECT,
				PermissionDictionary::TASK_NON_DEPARTMENT_DIRECT,
				PermissionDictionary::TASK_NON_DEPARTMENT_VIEW,
				PermissionDictionary::TASK_NON_DEPARTMENT_EDIT,
				PermissionDictionary::TASK_CLOSED_NON_DEPARTMENT_EDIT,
				PermissionDictionary::TASK_NON_DEPARTMENT_DELETE,

				PermissionDictionary::TASK_EXPORT,
				PermissionDictionary::TASK_IMPORT,
			],
			self::SECTION_TEMPLATES => [
				PermissionDictionary::TEMPLATE_CREATE,
				PermissionDictionary::TEMPLATE_DEPARTMENT_VIEW,
				PermissionDictionary::TEMPLATE_NON_DEPARTMENT_VIEW,
				PermissionDictionary::TEMPLATE_DEPARTMENT_EDIT,
				PermissionDictionary::TEMPLATE_NON_DEPARTMENT_EDIT,
				PermissionDictionary::TEMPLATE_REMOVE,
			],
			self::SECTION_ROBOTS => [
				PermissionDictionary::TASK_ROBOT_EDIT
			],
			self::SECTION_ACCESS => [
				PermissionDictionary::TASK_ACCESS_MANAGE
			]
		];
	}

	private function getRoleMembers(int $roleId): array
	{
		$members = [];
		$relations = (new \Bitrix\Tasks\Access\Role\RoleUtil($roleId))->getMembers(self::PREVIEW_LIMIT);
		foreach ($relations as $row)
		{
			$accessCode = $row['RELATION'];
			$members[$accessCode] = $this->getMemberInfo($accessCode);
		}

		return $members;
	}

	private function getMemberInfo(string $code)
	{
		$accessCode = new \Bitrix\Main\Access\AccessCode($code);
		$member = (new \Bitrix\Main\UI\AccessRights\DataProvider())->getEntity($accessCode->getEntityType(), $accessCode->getEntityId());
		return $member->getMetaData();
	}

	private function getRoleAccessRights(int $roleId): array
	{
		$permissions = (new \Bitrix\Tasks\Access\Role\RoleUtil($roleId))->getPermissions();

		$accessRights = [];
		foreach ($permissions as $permissionId => $value)
		{
			$accessRights[] = [
				'id' => $permissionId,
				'value' => $value
			];
		}

		return $accessRights;
	}
}