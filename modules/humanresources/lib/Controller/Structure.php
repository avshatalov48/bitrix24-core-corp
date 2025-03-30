<?php

namespace Bitrix\HumanResources\Controller;

use Bitrix\HumanResources\Access\Model\UserModel;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Access\StructureActionDictionary;
use Bitrix\HumanResources\Util\StructureHelper;
use Bitrix\HumanResources\Engine\Controller;
use Bitrix\HumanResources\Attribute;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\HumanResources\Config;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Request;
use Bitrix\Main\SystemException;

final class Structure extends Controller
{
	private readonly NodeRepository $nodeRepository;

	public function __construct(Request $request = null)
	{
		$this->nodeRepository = Container::getNodeRepository(true);
		parent::__construct($request);
	}

	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_STRUCTURE_VIEW_ACCESS
	)]
	public function getAction(Item\Structure $structure): ?array
	{
		try
		{
			$nodes = $this->nodeRepository->getAllByStructureId($structure->id);
		}
		catch (WrongStructureItemException $e)
		{
			$this->addErrors($e->getErrors()->toArray());

			return [];
		}
		catch (ObjectPropertyException|ArgumentException|SystemException $e)
		{
			return [];
		}

		$result = [];
		$rootNode = $this->nodeRepository->getRootNodeByStructureId($structure->id);
		if (!$rootNode)
		{
			return $result;
		}

		$result[] = StructureHelper::getNodeInfo($rootNode);
		foreach ($nodes as $node)
		{
			if ($node->id === $rootNode->id)
			{
				continue;
			}

			if ((int)$node->parentId !== 0 && $nodes->getItemById($node->parentId) === null)
			{
				$node->parentId = $rootNode->id;
			}

			$result[] = StructureHelper::getNodeInfo($node);
		}

		return $result;
	}

	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_STRUCTURE_VIEW_ACCESS
	)]
	public function dictionaryAction(): array
	{
		$userModel = UserModel::createFromId(CurrentUser::get()->getId());
		return [
			'currentUserPermissions' => [
				StructureActionDictionary::ACTION_STRUCTURE_VIEW => $userModel->getPermission(
					PermissionDictionary::HUMAN_RESOURCES_STRUCTURE_VIEW
				),
				StructureActionDictionary::ACTION_DEPARTMENT_EDIT => $userModel->getPermission(
					PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_EDIT
				),
				StructureActionDictionary::ACTION_DEPARTMENT_DELETE => $userModel->getPermission(
					PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_DELETE
				),
				StructureActionDictionary::ACTION_DEPARTMENT_CREATE => $userModel->getPermission(
					PermissionDictionary::HUMAN_RESOURCES_DEPARTMENT_CREATE
				),
				StructureActionDictionary::ACTION_EMPLOYEE_ADD_TO_DEPARTMENT => $userModel->getPermission(
					PermissionDictionary::HUMAN_RESOURCES_EMPLOYEE_ADD_TO_DEPARTMENT
				),
				StructureActionDictionary::ACTION_EMPLOYEE_REMOVE_FROM_DEPARTMENT => $userModel->getPermission(
					PermissionDictionary::HUMAN_RESOURCES_EMPLOYEE_REMOVE_FROM_DEPARTMENT
				),
				StructureActionDictionary::ACTION_CHAT_BIND_TO_STRUCTURE => $userModel->getPermission(
					PermissionDictionary::HUMAN_RESOURCES_CHAT_BIND_TO_STRUCTURE
				),
				StructureActionDictionary::ACTION_CHAT_UNBIND_TO_STRUCTURE => $userModel->getPermission(
					PermissionDictionary::HUMAN_RESOURCES_CHAT_UNBIND_TO_STRUCTURE
				),
				StructureActionDictionary::ACTION_CHANEL_BIND_TO_STRUCTURE => $userModel->getPermission(
					PermissionDictionary::HUMAN_RESOURCES_CHANEL_BIND_TO_STRUCTURE
				),
				StructureActionDictionary::ACTION_CHANEL_UNBIND_TO_STRUCTURE => $userModel->getPermission(
					PermissionDictionary::HUMAN_RESOURCES_CHANEL_UNBIND_TO_STRUCTURE
				),
				StructureActionDictionary::ACTION_USERS_ACCESS_EDIT => $userModel->isAdmin()
					? 1
					: $userModel->getPermission(PermissionDictionary::HUMAN_RESOURCES_USERS_ACCESS_EDIT),
				StructureActionDictionary::ACTION_USER_INVITE => $this->canInviteUsers()
					? PermissionVariablesDictionary::VARIABLE_ALL
					: PermissionVariablesDictionary::VARIABLE_NONE
				,
			],
			'permissionVariablesDictionary' => PermissionVariablesDictionary::getVariables(),
			'firstTimeOpened' => \CUserOptions::GetOption("humanresources", 'first_time_opened', 'N')
		];
	}

	private function canInviteUsers(): bool
	{
		if (Config\Storage::instance()->isHRInvitePermissionAvailable())
		{
			$userModel = UserModel::createFromId(CurrentUser::get()->getId());

			return (bool)$userModel->getPermission(PermissionDictionary::HUMAN_RESOURCES_USER_INVITE);
		}

		return
			\Bitrix\Intranet\CurrentUser::get()->canDoOperation('edit_all_users')
			|| (Loader::includeModule('bitrix24') && Option::get('bitrix24', 'allow_invite_users', 'N') === 'Y')
		;
	}
}