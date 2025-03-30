<?php

namespace Bitrix\HumanResources\Controller\Structure\Node;

use Bitrix\HumanResources\Access\StructureActionDictionary;
use Bitrix\HumanResources\Attribute;
use Bitrix\HumanResources\Contract\Repository\RoleRepository;
use Bitrix\HumanResources\Contract\Service\UserService;
use Bitrix\HumanResources\Engine\Controller;
use Bitrix\HumanResources\Contract\Repository\NodeMemberRepository;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Contract\Service\NodeMemberService;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\AccessibleItemType;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\Main;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Main\SystemException;

final class Member extends Controller
{
	private readonly NodeMemberRepository $nodeMemberRepository;
	private readonly NodeMemberService $nodeMemberService;
	private readonly RoleRepository $roleRepository;
	private readonly UserService $userService;

	private const ERROR_CODE_MEMBER_ALREADY_BELONGS_TO_NODE = 'MEMBER_ALREADY_BELONGS_TO_NODE';

	public function __construct(Request $request = null)
	{
		$this->nodeMemberRepository = Container::getNodeMemberRepository();
		$this->nodeMemberService = Container::getNodeMemberService();
		$this->roleRepository = Container::getRoleRepository();
		$this->userService = Container::getUserService();

		parent::__construct($request);
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws SystemException
	 * @throws CreationFailedException
	 */
	#[Attribute\Access\LogicOr(
		new Attribute\StructureActionAccess(
			permission: StructureActionDictionary::ACTION_DEPARTMENT_CREATE,
			itemType: AccessibleItemType::NODE,
			itemIdRequestKey: 'nodeId',
		),
		new Attribute\StructureActionAccess(
			permission: StructureActionDictionary::ACTION_EMPLOYEE_ADD_TO_DEPARTMENT,
			itemType: AccessibleItemType::NODE,
			itemIdRequestKey: 'nodeId',
		),
	)]
	public function addAction(
		Item\NodeMember $nodeMember,
		Item\Node $node,
	): array
	{
		$nodeMember->nodeId = $node->id;
		$this->nodeMemberRepository->create($nodeMember);

		return [];
	}

	#[Attribute\Access\LogicOr(
		new Attribute\StructureActionAccess(
			permission: StructureActionDictionary::ACTION_DEPARTMENT_CREATE,
			itemType: AccessibleItemType::NODE,
			itemIdRequestKey: 'nodeId',
		),
		new Attribute\StructureActionAccess(
			permission: StructureActionDictionary::ACTION_EMPLOYEE_ADD_TO_DEPARTMENT,
			itemType: AccessibleItemType::NODE,
			itemIdRequestKey: 'nodeId',
		),
		new Attribute\StructureActionAccess(
			permission: StructureActionDictionary::ACTION_EMPLOYEE_REMOVE_FROM_DEPARTMENT,
			itemType: AccessibleItemType::NODE,
			itemIdRequestKey: 'nodeId',
		),
	)]
	public function moveAction(
		Item\NodeMember $nodeMember,
		Item\Node $node,
	):array
	{
		try
		{
			$this->nodeMemberService->moveMember($nodeMember, $node);
		}
		catch (UpdateFailedException $exception)
		{
			$this->addErrors($exception->getErrors()->toArray());
		}

		return [];
	}

	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_EMPLOYEE_ADD_TO_DEPARTMENT,
		itemType: AccessibleItemType::NODE,
		itemIdRequestKey: 'targetNodeId',
	)]
	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_EMPLOYEE_REMOVE_FROM_DEPARTMENT,
		itemType: AccessibleItemType::NODE,
		itemIdRequestKey: 'nodeId',
	)]
	public function moveUserAction(
		Item\NodeMember $nodeUserMember,
		Item\Node $targetNode,
	):array
	{
		if ($nodeUserMember->nodeId === $targetNode->id)
		{
			$this->addError(
				new Error(
					'Member already belongs to Node',
					self::ERROR_CODE_MEMBER_ALREADY_BELONGS_TO_NODE,
				),
			);

			return[];
		}

		try
		{
			$employeeRole = $this->roleRepository->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['EMPLOYEE']);
			$nodeUserMember->role = $employeeRole->id;
			$this->nodeMemberService->moveMember($nodeUserMember, $targetNode);
		}
		catch (UpdateFailedException $exception)
		{
			$errors = $exception->getErrors();
			foreach ($errors as $error)
			{
				if ($error->getCode() === self::ERROR_CODE_MEMBER_ALREADY_BELONGS_TO_NODE)
				{
					$this->addError(new Error(
						'Member already belongs to Node',
						$error->getCode(),
					));

					return[];
				}
			}
			$this->addErrors($errors->toArray());
		}

		return [];
	}

	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_STRUCTURE_VIEW,
		itemType: AccessibleItemType::NODE,
		itemIdRequestKey: 'nodeId',
	)]
	public function getUserMemberAction(
		Item\User $user,
		Item\Node $node,
	): array
	{
		$nodeMember = $this->nodeMemberRepository->findByEntityTypeAndEntityIdAndNodeId(
			entityType: MemberEntityType::USER,
			entityId: $user->id,
			nodeId: $node->id,
		);

		if (!$nodeMember)
		{
			$this->addError(new Main\Error('Member not found'));

			return [];
		}

		return [
			'id' => $nodeMember->id,
			'roles' => $nodeMember->roles,
		];
	}

	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_EMPLOYEE_REMOVE_FROM_DEPARTMENT,
		itemType: AccessibleItemType::NODE_MEMBER,
		itemIdRequestKey: 'nodeMemberId',
	)]
	public function deleteAction(
		Item\NodeMember $member,
	): array
	{
		return [
			'success' => $this->nodeMemberRepository->remove($member),
		];
	}

	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_EMPLOYEE_REMOVE_FROM_DEPARTMENT,
		itemType: AccessibleItemType::NODE,
		itemIdRequestKey: 'nodeId',
	)]
	public function deleteUserAction(
		Item\NodeMember $nodeUserMember,
	): array
	{
		$nodeMember = $this->nodeMemberService->removeUserMemberFromDepartment($nodeUserMember);

		return [
			'userMovedToRoot' => $nodeMember !== null,
		];
	}

	#[Attribute\StructureActionAccess(StructureActionDictionary::ACTION_STRUCTURE_VIEW)]
	public function countAction(
		Item\Structure $structure,
	): array
	{
		return $this->nodeMemberRepository->countAllByStructureAndGroupByNode($structure);
	}

	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_EMPLOYEE_ADD_TO_DEPARTMENT,
		itemType: AccessibleItemType::NODE,
		itemIdRequestKey: 'nodeId',
	)]
	public function addUserMemberAction(Item\Node $node, array $userIds, Item\Role $role): ?array
	{
		$result = [];
		try
		{
			foreach ($userIds as $userId)
			{
				$userId = (int)$userId;
				if (!$userId)
				{
					continue;
				}

				$member = new NodeMember(
					MemberEntityType::USER,
					$userId,
					$node->id,
					true,
					role: $role->id,
				);
				$this->nodeMemberRepository->create($member);

				$result['members'][] = $member;
			}
		}
		catch (CreationFailedException $e)
		{
			$this->addErrors($e->getErrors()->toArray());
		}
		catch (\Throwable $e)
		{
			$this->addError(new Error(Loc::getMessage('HUMAN_RESOURCES_MEMBER_ADD_FAILED')));
		}

		$result['userCount'] = $this->nodeMemberRepository->countAllByByNodeId($node->id);
		return $result;
	}

	/**
	 * @param Item\Node $node
	 * @param array{
	 *      MEMBER_HEAD?: list<int>,
	 *      MEMBER_EMPLOYEE?: list<int>,
	 *      MEMBER_DEPUTY_HEAD?: list<int>
	 * } $userIds
 *
	 * @return array
	 * @throws ArgumentException
	 * @throws Main\DB\DuplicateEntryException
	 * @throws Main\ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	#[Attribute\Access\LogicOr(
		new Attribute\StructureActionAccess(
			permission: StructureActionDictionary::ACTION_DEPARTMENT_CREATE,
			itemType: AccessibleItemType::NODE,
			itemParentIdRequestKey: 'parentId',
		),
		new Attribute\StructureActionAccess(
			permission: StructureActionDictionary::ACTION_EMPLOYEE_ADD_TO_DEPARTMENT,
			itemType: AccessibleItemType::NODE,
			itemIdRequestKey: 'nodeId',
		),
	)]
	public function saveUserListAction(Item\Node $node, array $userIds = []): array
	{
		$userMovedToRootIds = [];
		$nodeMemberCollection =  $this->nodeMemberService->saveUsersToDepartment($node, $userIds);
		foreach ($nodeMemberCollection as $nodeMember)
		{
			if ($nodeMember->nodeId === $node->id)
			{
				continue;
			}

			$userMovedToRootIds[] = $nodeMember->entityId;
		}

		return [
			'userMovedToRootIds' => $userMovedToRootIds,
		];
	}

	public function findAction(Item\Node $node, string $query)
	{
		return $this->userService->findByNodeAndSearchQuery($node, $query);
	}

	#[Attribute\Access\LogicOr(
		new Attribute\StructureActionAccess(
			permission: StructureActionDictionary::ACTION_DEPARTMENT_CREATE,
			itemType: AccessibleItemType::NODE,
			itemParentIdRequestKey: 'parentId',
		),
		new Attribute\StructureActionAccess(
			permission: StructureActionDictionary::ACTION_EMPLOYEE_ADD_TO_DEPARTMENT,
			itemType: AccessibleItemType::NODE,
			itemIdRequestKey: 'nodeId',
		),
	)]
	public function moveUserListToDepartmentAction(Item\Node $node, array $userIds = []): array
	{
		$departmentUserIds = [];
		foreach ($userIds as $ids)
		{
			$ids = array_filter(array_map('intval', $ids));
			if (empty($ids))
			{
				continue;
			}

			if (empty($departmentUserIds))
			{
				$departmentUserIds = $ids;

				continue;
			}

			$departmentUserIds = array_merge($departmentUserIds, $ids);
			$departmentUserIds = array_unique($departmentUserIds);
		}

		$result['updatedDepartmentIds'] = [];
		$userCollection = $this->nodeMemberRepository->findAllByEntityIdsAndEntityTypeAndNodeType(
			entityIds: $departmentUserIds,
			entityType: MemberEntityType::USER,
			nodeType: NodeEntityType::DEPARTMENT,
		);
		foreach ($userCollection as $nodeMember)
		{
			if ($nodeMember->nodeId === $node->id)
			{
				continue;
			}

			$result['updatedDepartmentIds'][] = $nodeMember->nodeId;
		}

		$this->nodeMemberService->moveUsersToDepartment($node, $userIds);
		$result['userCount'] = $this->nodeMemberRepository->countAllByByNodeId($node->id);

		return $result;
	}
}