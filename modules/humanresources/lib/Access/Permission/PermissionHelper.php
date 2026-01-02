<?php

namespace Bitrix\HumanResources\Access\Permission;

use Bitrix\HumanResources\Access\Enum\PermissionValueType;
use Bitrix\HumanResources\Access\Model\UserModel;
use Bitrix\HumanResources\Item\Access\Permission;
use Bitrix\HumanResources\Item\Collection\Access\PermissionCollection;
use Bitrix\HumanResources\Service\Access\Structure\StructureAccessService;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Type\StructureAction;

final class PermissionHelper
{
	public static function getStructureActionByPermissionId(string $permissionId): StructureAction
	{
		return match ($permissionId)
		{
			PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW, PermissionDictionary::HUMAN_RESOURCES_TEAM_VIEW => StructureAction::ViewAction,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_CREATE, PermissionDictionary::HUMAN_RESOURCES_TEAM_CREATE => StructureAction::CreateAction,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_DELETE, PermissionDictionary::HUMAN_RESOURCES_TEAM_DELETE => StructureAction::DeleteAction,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_EDIT, PermissionDictionary::HUMAN_RESOURCES_TEAM_EDIT => StructureAction::UpdateAction,
			PermissionDictionary::HUMAN_RESOURCES_EMPLOYEE_ADD_TO_DEPARTMENT, PermissionDictionary::HUMAN_RESOURCES_TEAM_MEMBER_ADD => StructureAction::AddMemberAction,
			PermissionDictionary::HUMAN_RESOURCES_EMPLOYEE_REMOVE_FROM_DEPARTMENT, PermissionDictionary::HUMAN_RESOURCES_TEAM_MEMBER_REMOVE => StructureAction::RemoveMemberAction,
			PermissionDictionary::HUMAN_RESOURCES_TEAM_SETTINGS_EDIT, PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_SETTINGS_EDIT => StructureAction::EditSettingsAction,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_CHAT_EDIT, PermissionDictionary::HUMAN_RESOURCES_TEAM_CHAT_EDIT => StructureAction::EditChatAction,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_CHANNEL_EDIT, PermissionDictionary::HUMAN_RESOURCES_TEAM_CHANNEL_EDIT => StructureAction::EditChannelAction,
			PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_COLLAB_EDIT, PermissionDictionary::HUMAN_RESOURCES_TEAM_COLLAB_EDIT => StructureAction::EditCollabAction,
			PermissionDictionary::HUMAN_RESOURCES_USER_INVITE => StructureAction::InviteUserAction,
			default => throw new \InvalidArgumentException('Permission has no structure action'),
		};
	}

	public static function getPermissionValue(string $permissionId, int $userId): PermissionCollection
	{
		$permissionCollection = new PermissionCollection();
		if (!$userId)
		{
			if (!PermissionDictionary::isTeamDependentVariablesPermission($permissionId))
			{
				return $permissionCollection->add(
					Permission::getWithoutRoleId($permissionId, PermissionVariablesDictionary::VARIABLE_NONE),
				);
			}

			$teamPermissionId = $permissionId . '_' . PermissionValueType::TeamValue->value;
			$departmentPermissionId = $permissionId . '_' . PermissionValueType::DepartmentValue->value;

			return new PermissionCollection(
				Permission::getWithoutRoleId(
					$teamPermissionId,
					PermissionVariablesDictionary::VARIABLE_NONE,
				),
				Permission::getWithoutRoleId(
					$departmentPermissionId,
					PermissionVariablesDictionary::VARIABLE_NONE,
				),
			);
		}

		$structureAccessService = new StructureAccessService();
		$structureAccessService->setUserId($userId);
		if (PermissionDictionary::isTeamDependentVariablesPermission($permissionId))
		{
			$structureAction = self::getStructureActionByPermissionId($permissionId);
			$structureAccessService->setAction($structureAction);

			return $structureAccessService->getPermissionValue(NodeEntityType::TEAM);
		}

		$userModel = UserModel::createFromId($userId);
		if ($userModel->isAdmin())
		{
			if (PermissionDictionary::getType($permissionId) === PermissionDictionary::TYPE_TOGGLER)
			{
				return $permissionCollection->add(
					Permission::getWithoutRoleId($permissionId, PermissionDictionary::VALUE_YES),
				);
			}
			else
			{
				return $permissionCollection->add(
					Permission::getWithoutRoleId($permissionId, PermissionVariablesDictionary::VARIABLE_ALL),
				);
			}
		}

		if (PermissionDictionary::getType($permissionId) === PermissionDictionary::TYPE_TOGGLER)
		{
			return $permissionCollection->add(
				Permission::getWithoutRoleId($permissionId, (int)$userModel->getPermission($permissionId)),
			);
		}

		$structureAction = self::getStructureActionByPermissionId($permissionId);
		$structureAccessService->setAction($structureAction);

		return $structureAccessService->getPermissionValue();
	}
}