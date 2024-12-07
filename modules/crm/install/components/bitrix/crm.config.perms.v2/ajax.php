<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !==true) die();

\Bitrix\Main\Loader::requireModule('crm');


use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Commands\DeleteRoleCommand;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Commands\DTO\UserGroupsData;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Commands\UpdateRoleCommand;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Queries\QueryRoles;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Validators\UserGroupDataValidator;
use Bitrix\Crm\Security\Role\Utils\RoleManagerUtils;
use Bitrix\Crm\Security\Role\Validators\DeleteRoleValidator;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Request;

class CrmConfigPermsV2AjaxController extends Controller
{
	private UpdateRoleCommand $updateRoleCommand;

	private DeleteRoleCommand $deleteRoleCommand;

	private RoleManagerUtils $utils;

	private DeleteRoleValidator $deleteRoleValidator;

	private UserGroupDataValidator $userGroupDataValidator;

	private QueryRoles $queryRoles;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->updateRoleCommand = UpdateRoleCommand::getInstance();
		$this->deleteRoleValidator = DeleteRoleValidator::getInstance();
		$this->deleteRoleCommand = DeleteRoleCommand::getInstance();
		$this->userGroupDataValidator = UserGroupDataValidator::getInstance();
		$this->queryRoles = QueryRoles::getInstance();
		$this->utils = RoleManagerUtils::getInstance();
	}

	public function configureActions(): array
	{
		return [
			'save' => [
				'prefilters' => [
					new ActionFilter\Authentication(),
					new ActionFilter\HttpMethod(['POST']),
					new ActionFilter\Csrf(),
				],
			],
			'delete' => [
				'prefilters' => [
					new ActionFilter\Authentication(),
					new ActionFilter\HttpMethod(['POST']),
					new ActionFilter\Csrf(),
				],
			],
			'load' => [
				'prefilters' => [
					new ActionFilter\Authentication(),
					new ActionFilter\HttpMethod(['POST']),
					new ActionFilter\Csrf(),
				],
			]
		];
	}

	public function saveAction($userGroups = []): AjaxJson
	{
		if (!$this->utils->hasAccessToEditPerms())
		{
			return $this->createAccessDeniedResponse();
		}

		$tariffResult = $this->utils->checkTariffRestriction();
		if (!$tariffResult->isSuccess())
		{
			return AjaxJson::createError($tariffResult->getErrorCollection());
		}

		$userGroupDTOs = UserGroupsData::makeFromArray($userGroups);

		$validationResult = $this->userGroupDataValidator->validate($userGroupDTOs);
		if (!$validationResult->isSuccess())
		{
			return AjaxJson::createError($validationResult->getErrorCollection());
		}

		$this->updateRoleCommand->execute($userGroupDTOs);

		return AjaxJson::createSuccess();
	}

	public function deleteAction($roleId): AjaxJson
	{
		if (!$this->utils->hasAccessToEditPerms())
		{
			return $this->createAccessDeniedResponse();
		}

		$tariffResult = $this->utils->checkTariffRestriction();
		if (!$tariffResult->isSuccess())
		{
			return AjaxJson::createError($tariffResult->getErrorCollection());
		}

		$validationResult = $this->deleteRoleValidator->validate($roleId);
		if (!$validationResult->isSuccess())
		{
			return AjaxJson::createError($validationResult->getErrorCollection());
		}

		$this->deleteRoleCommand->execute($roleId);

		return AjaxJson::createSuccess();
	}

	public function loadAction(): AjaxJson
	{
		if (!$this->utils->hasAccessToEditPerms())
		{
			return $this->createAccessDeniedResponse();
		}

		$rolesData = $this->queryRoles->execute();

		return AjaxJson::createSuccess([
			'USER_GROUPS' => $rolesData->userGroups,
			'ACCESS_RIGHTS' => $rolesData->accessRights
		]);
	}

	private function createAccessDeniedResponse(): AjaxJson
	{
		$errCollection = new \Bitrix\Main\ErrorCollection();
		$errCollection->add([new \Bitrix\Main\Error('ACCESS DENIED')]);

		return AjaxJson::createDenied($errCollection);
	}

}