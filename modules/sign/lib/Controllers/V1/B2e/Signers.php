<?php

namespace Bitrix\Sign\Controllers\V1\B2e;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute\Access\LogicAnd;
use Bitrix\Sign\Attribute\ActionAccess;
use Bitrix\Sign\Engine\Controller;
use Bitrix\Sign\Operation;
use Bitrix\Sign\Result\Operation\Member\ValidateEntitySelectorMembersResult;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Access\AccessibleItemType;
use Bitrix\Sign\Type\Hr\EntitySelector\EntityType as HrEntityType;
use Bitrix\Sign\Type\Member\EntityType;

class Signers extends Controller
{
	#[ActionAccess(
		permission: ActionDictionary::ACTION_B2E_SIGNERS_LIST_DELETE,
		itemType: AccessibleItemType::SIGNERS_LIST,
		itemIdOrUidRequestKey: 'listId',
	)]
	public function deleteListAction(int $listId): array
	{
		$list = Container::instance()->getSignersListService()->getById($listId);
		if ($list === null)
		{
			$this->addErrorByMessage('List not found');

			return [];
		}

		$result = (new Operation\Signers\DeleteList($list))->launch();
		$this->addErrorsFromResult($result);

		return [];
	}

	#[ActionAccess(
		permission: ActionDictionary::ACTION_B2E_SIGNERS_LIST_EDIT,
		itemType: AccessibleItemType::SIGNERS_LIST,
		itemIdOrUidRequestKey: 'listId',
	)]
	public function deleteSignersFromListAction(int $listId, array $userIds): array
	{
		$list = Container::instance()->getSignersListService()->getById($listId);
		if ($list === null)
		{
			$this->addErrorByMessage('List not found');

			return [];
		}

		$currentUserId = (int)CurrentUser::get()->getId();
		if ($currentUserId < 1)
		{
			$this->addErrorByMessage('Current user is not authorized');

			return [];
		}

		$result = Container::instance()->getSignersListService()->deleteUsersFromList(
			$listId,
			$userIds,
			$currentUserId,
		);
		$this->addErrorsFromResult($result);

		return [];
	}

	#[ActionAccess(
		permission: ActionDictionary::ACTION_B2E_SIGNERS_LIST_EDIT,
		itemType: AccessibleItemType::SIGNERS_LIST,
		itemIdOrUidRequestKey: 'listId',
	)]
	public function addSignersToListAction(int $listId, array $members, bool $excludeRejected = true): array
	{
		$list = Container::instance()->getSignersListService()->getById($listId);
		if ($list === null)
		{
			$this->addErrorByMessage('List not found');

			return [];
		}

		$result = (new Operation\Member\Validation\ValidateEntitySelectorMembers($members))->launch();
		if (!$result instanceof ValidateEntitySelectorMembersResult)
		{
			$this->addErrorsFromResult($result);

			return [];
		}

		$result = (new Operation\Member\GetMembersFromUserPartyEntities($result->entities, $excludeRejected))->launch();

		$userMembers = $result->members->filterByEntityTypes(EntityType::USER);
		$userIds = [];
		foreach ($userMembers as $user)
		{
			$userIds[$user->entityId] = true;
		}

		if ($result->departments->count() > 0)
		{
			if (!Loader::includeModule('humanresources'))
			{
				$this->addErrorByMessage('humanresources module is not installed');

				return [];
			}

			$nodeMemberService = \Bitrix\HumanResources\Service\Container::instance()->getNodeMemberService();

			foreach ($result->departments as $department)
			{
				$employees = $nodeMemberService->getAllEmployees(
					nodeId: $department->entityId,
					withAllChildNodes: $department->entityType !== HrEntityType::FlatDepartment,
				);

				foreach ($employees->getIterator() as $employee)
				{
					$userIds[$employee->entityId] = true;
				}
			}
		}

		$userIds = array_keys($userIds);

		$currentUserId = (int)CurrentUser::get()->getId();

		if ($currentUserId < 1)
		{
			$this->addErrorByMessage('Current user is not authorized');

			return [];
		}

		$result = Container::instance()->getSignersListService()->addUsersToList(
			$listId,
			$userIds,
			$currentUserId,
			ignoreDuplicates: true,
		);
		$this->addErrorsFromResult($result);
		return [];
	}

	#[LogicAnd(
		new ActionAccess(
			permission: ActionDictionary::ACTION_B2E_SIGNERS_LIST_READ,
			itemType: AccessibleItemType::SIGNERS_LIST,
			itemIdOrUidRequestKey: 'listId',
		),
		new ActionAccess(ActionDictionary::ACTION_B2E_SIGNERS_LIST_ADD),
	)]
	public function copyListAction(int $listId): array
	{
		if ($listId < 1)
		{
			$this->addErrorByMessage('Incorrect list id');

			return [];
		}

		$list = Container::instance()->getSignersListService()->getById($listId);
		if ($list === null)
		{
			$this->addErrorByMessage('List not found');

			return [];
		}

		$createdByUserId = (int)CurrentUser::get()->getId();

		if ($createdByUserId < 1)
		{
			$this->addErrorByMessage('Current user is not authorized');

			return [];
		}

		$result = (new Operation\Signers\CopyList($list, $createdByUserId))->launch();
		$this->addErrorsFromResult($result);

		return [];
	}

	#[ActionAccess(
		permission: ActionDictionary::ACTION_B2E_SIGNERS_LIST_ADD,
	)]
	public function createListAction(string $title): array
	{
		$currentUserId = (int)CurrentUser::get()->getId();
		if ($currentUserId < 1)
		{
			$this->addErrorByMessage('Current user is not authorized');

			return [];
		}

		$result = Container::instance()->getSignersListService()->createList(
			$title,
			$currentUserId,
		);
		$this->addErrorsFromResult($result);

		return [];
	}

	#[ActionAccess(
		permission: ActionDictionary::ACTION_B2E_SIGNERS_LIST_EDIT,
		itemType: AccessibleItemType::SIGNERS_LIST,
		itemIdOrUidRequestKey: 'listId',
	)]
	public function renameListAction(int $listId, string $title): array
	{
		if ($listId < 1)
		{
			$this->addErrorByMessage('Incorrect list id');

			return [];
		}

		$list = Container::instance()->getSignersListService()->getById($listId);
		if ($list === null)
		{
			$this->addErrorByMessage('List not found');

			return [];
		}

		$currentUserId = (int)CurrentUser::get()->getId();
		if ($currentUserId < 1)
		{
			$this->addErrorByMessage('Current user is not authorized');

			return [];
		}

		$result = Container::instance()->getSignersListService()->renameList(
			$listId,
			$title,
			$currentUserId,
		);
		$this->addErrorsFromResult($result);

		return [];
	}
}
