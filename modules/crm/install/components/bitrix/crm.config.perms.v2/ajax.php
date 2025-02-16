<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !==true) die();

\Bitrix\Main\Loader::requireModule('crm');

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Security\Role\Manage\RoleManagerSelectionFactory;
use Bitrix\Crm\Security\Role\Manage\RoleSelectionManager;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Commands\DeleteRoleCommand;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Commands\DTO\UserGroupsData;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Commands\UpdateRoleCommand;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Queries\QueryRoles;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Utils\PermCodeTransformer;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Validators\UserGroupDataValidator;
use Bitrix\Crm\Security\Role\Utils\RoleManagerUtils;
use Bitrix\Crm\Security\Role\Validators\DeleteRoleValidator;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Request;
use Bitrix\Main\Result;

class CrmConfigPermsV2AjaxController extends \Bitrix\Main\Engine\JsonController
{
	private UpdateRoleCommand $updateRoleCommand;

	private DeleteRoleCommand $deleteRoleCommand;

	private RoleManagerUtils $utils;

	private DeleteRoleValidator $deleteRoleValidator;

	private UserGroupDataValidator $userGroupDataValidator;

	private QueryRoles $queryRoles;

	private ?RoleSelectionManager $manager;
	private UserPermissions $userPermissions;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->updateRoleCommand = UpdateRoleCommand::getInstance();
		$this->deleteRoleValidator = DeleteRoleValidator::getInstance();
		$this->deleteRoleCommand = DeleteRoleCommand::getInstance();
		$this->userGroupDataValidator = UserGroupDataValidator::getInstance();
		$this->utils = RoleManagerUtils::getInstance();
		$this->userPermissions = Container::getInstance()->getUserPermissions();
	}

	private function getRoleManager(array $parameters): ?RoleSelectionManager
	{
		$criterion = $parameters['criterion'] ?? null;
		$sectionCode = $parameters['sectionCode'] ?? null;
		$isAutomation = $parameters['isAutomation'] ?? false;

		return (new RoleManagerSelectionFactory())
			->setCustomSectionCode($sectionCode)
			->setAutomation($isAutomation)
			->create($criterion)
		;
	}

	public function saveAction(array $userGroups = [], array $deletedUserGroups = [], array $parameters = []): ?array
	{
		$this->manager = $this->getRoleManager($parameters);
		if ($this->manager === null)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		$this->queryRoles = new QueryRoles($this->manager);

		if (!$this->canEditRights())
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return null;
		}

		$tariffResult = $this->utils->checkTariffRestriction();
		if (!$tariffResult->isSuccess())
		{
			$this->addErrors($tariffResult->getErrors());

			return null;
		}

		\Bitrix\Crm\Security\Role\Utils\RolePermissionLogContext::getInstance()->set([
			'component' => 'crm.config.perms.v2',
			'criterion' => (string)($parameters['criterion'] ?? null),
			'sectionCode' => (string)($parameters['sectionCode'] ?? null),
			'isAutomation' => (bool)($parameters['isAutomation'] ?? false)
		]);
		$deleteResult = $this->deleteUserGroups($deletedUserGroups);
		if (!$deleteResult->isSuccess())
		{
			$this->addErrors($deleteResult->getErrors());

			return null;
		}

		$userGroupDTOs = UserGroupsData::makeFromArray($userGroups, $this->manager->getGroupCode());

		$validationResult = $this->userGroupDataValidator->validate($userGroupDTOs);
		if (!$validationResult->isSuccess())
		{
			$this->addErrors($validationResult->getErrors());

			return null;
		}

		if (!$this->isSaveUserGroupsAllowed($userGroupDTOs))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return null;
		}

		$preSaveCheckResult = $this->manager->preSaveChecks($userGroupDTOs);
		if (!$preSaveCheckResult->isSuccess())
		{
			$this->addErrors($preSaveCheckResult->getErrors());

			return null;
		}

		$updateResult = $this->updateRoleCommand->execute($userGroupDTOs);
		if (!$updateResult->isSuccess())
		{
			$this->addErrors($updateResult->getErrors());

			return null;
		}
		\Bitrix\Crm\Security\Role\Utils\RolePermissionLogContext::getInstance()->clear();

		$rolesData = $this->queryRoles->execute();

		return [
			'USER_GROUPS' => $rolesData->userGroups,
			// it's unnecessary, can be removed safely after the UI update is out
			'ACCESS_RIGHTS' => $rolesData->accessRights
		];
	}

	private function deleteUserGroups(array $userGroupsToDelete): \Bitrix\Main\Result
	{
		$result = new Result();

		foreach ($userGroupsToDelete as $roleId)
		{
			$validationResult = $this->deleteRoleValidator->validate($roleId);
			if (!$validationResult->isSuccess())
			{
				$result->addErrors($validationResult->getErrors());
				continue;
			}

			$deleteResult = $this->deleteRoleCommand->execute($roleId);
			if (!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}

	private function canEditRights(): bool
	{
		return $this->manager->hasPermissionsToEditRights();
	}

	/**
	 * @param UserGroupsData[] $userGroups
	 * @return bool
	 */
	private function isSaveUserGroupsAllowed(array $userGroups): bool
	{
		$checkedEntities = [];
		$transformer = PermCodeTransformer::getInstance();

		foreach ($userGroups as $userGroup)
		{
			foreach ($userGroup->accessRights as $accessRight)
			{
				try {
					$permission = $transformer->decodeAccessRightCode($accessRight->id);
				}
				catch (ArgumentException $e) {
					return false;
				}

				$isChecked = in_array($permission->entityCode, $checkedEntities, true);
				if ($isChecked)
				{
					continue;
				}

				if (!$this->userPermissions->canUpdatePermission($permission))
				{
					return false;
				}

				$checkedEntities[] = $permission->entityCode;
			}
		}

		return true;
	}
}
