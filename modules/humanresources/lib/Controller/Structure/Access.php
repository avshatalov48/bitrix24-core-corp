<?php

namespace Bitrix\HumanResources\Controller\Structure;

use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Engine\Controller;
use Bitrix\HumanResources\Service;
use Bitrix\HumanResources\Service\Access\Structure\StructureAccessService;
use Bitrix\HumanResources\Type\StructureAction;
use Bitrix\Main\Request;

final class Access extends Controller
{
	private readonly StructureAccessService $accessService;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);
		$this->accessService = new Service\Access\Structure\StructureAccessService();
	}

	/**
	 * Returns department permissions for the current user
	 * @return array{
	 * 	canCreateNewDepartment: bool,
	 * 	firstPossibleParentForNewDepartment: ?\Bitrix\HumanResources\Item\Node,
	 * 	canInviteUsers: bool,
	 * 	needToBeMemberOfNewDepartment: bool,
	 * }
	 */
	public function getDepartmentPermissionsAction(): array
	{
		$this->accessService->setAction(StructureAction::CreateAction);
		$canCreateNewDepartment =  $this->accessService->canDoActionWithAnyNode();
		$firstPossibleParentForNewDepartment = $this->accessService->findFirstPossibleAvailableNode();

		$this->accessService->setAction(StructureAction::InviteUserAction);
		$canInviteUsers = $this->accessService->canDoActionWithAnyNode();
		$needToBeMemberOfNewDepartment = (int)$this->accessService->getPermissionValue()->getFirst()?->value < PermissionVariablesDictionary::VARIABLE_ALL;

		return [
			'canCreateNewDepartment' => $canCreateNewDepartment,
			'firstPossibleParentForNewDepartment' => $firstPossibleParentForNewDepartment,
			'canInviteUsers' => $canInviteUsers,
			'needToBeMemberOfNewDepartment' =>$needToBeMemberOfNewDepartment,
		];
	}
}