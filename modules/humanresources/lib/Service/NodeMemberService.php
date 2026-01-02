<?php

namespace Bitrix\HumanResources\Service;

use Bitrix\HumanResources\Builder\Structure\Filter\Column\EntityIdFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\Node\NodeTypeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\NodeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\NodeMemberFilter;
use Bitrix\HumanResources\Builder\Structure\Sort\NodeSort;
use Bitrix\HumanResources\Enum\DepthLevel;
use Bitrix\HumanResources\Enum\Direction;
use Bitrix\HumanResources\Enum\SortDirection;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Internals\Service\Container as InternalContainer;
use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Item\Collection\NodeMemberCollection;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Builder\Structure\NodeMemberDataBuilder;
use Bitrix\HumanResources\Type;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Type\MemberSubordinateRelationType;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Type\NodeEntityTypeCollection;
use Bitrix\HumanResources\Type\StructureRole;
use Bitrix\HumanResources\Util\StructureHelper;
use Bitrix\HumanResources\Exception\DeleteFailedException;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class NodeMemberService implements Contract\Service\NodeMemberService
{
	private readonly Contract\Repository\NodeMemberRepository $nodeMemberRepository;
	private readonly Contract\Repository\RoleRepository $roleRepository;
	private readonly Contract\Repository\NodeRepository $nodeRepository;

	/**
	 * @var \Bitrix\HumanResources\Contract\Util\CacheManager
	 */
	private Contract\Util\CacheManager $cacheManager;

	public function __construct(
		?Contract\Repository\NodeMemberRepository $nodeMemberRepository = null,
		?Contract\Repository\RoleRepository $roleRepository = null,
		?Contract\Repository\NodeRepository $nodeRepository = null,
	)
	{
		$this->nodeMemberRepository = $nodeMemberRepository ?? Container::getNodeMemberRepository();
		$this->roleRepository = $roleRepository ?? Container::getRoleRepository();
		$this->nodeRepository = $nodeRepository ?? Container::getNodeRepository();
		$this->cacheManager = Container::getCacheManager();
		$this->cacheManager->setTtl(86400);
	}

	public function getMemberInformation(int $memberId): Item\NodeMember
	{
		return $this->nodeMemberRepository->findById($memberId);
	}

	/**
	 * Calculates relation between members with id $memberId and member with id $targetMemberId
	 * Simplified: Who is member for targetMember
	 *
	 * @param int $memberId
	 * @param int $targetMemberId
	 *
	 * @return MemberSubordinateRelationType
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getMemberSubordination(int $memberId, int $targetMemberId): Type\MemberSubordinateRelationType
	{
		$cacheKey = sprintf(self::MEMBER_TO_MEMBER_SUBORDINATE_CACHE_KEY, $memberId, $targetMemberId);

		$cacheValue = $this->cacheManager->getData($cacheKey);
		if ($cacheValue !== null)
		{
			return Type\MemberSubordinateRelationType::tryFrom($cacheValue);
		}

		if ($memberId === $targetMemberId)
		{
			$this->cacheManager->setData($cacheKey, Type\MemberSubordinateRelationType::RELATION_ITSELF);

			return Type\MemberSubordinateRelationType::RELATION_ITSELF;
		}

		$member = $this->nodeMemberRepository->findById($memberId);
		$targetMember = $this->nodeMemberRepository->findById($targetMemberId);

		if (
			($member->entityType !== $targetMember->entityType)
			|| (empty($member?->roles) || empty($targetMember?->roles))
		)
		{
			$this->cacheManager->setData($cacheKey, Type\MemberSubordinateRelationType::RELATION_OTHER);

			return Type\MemberSubordinateRelationType::RELATION_OTHER;
		}

		$memberNode = $this->nodeRepository->getById(
			nodeId: $member->nodeId,
			needDepth: true,
		);
		$targetMemberNode = $this->nodeRepository->getById(
			nodeId: $targetMember->nodeId,
			needDepth: true,
		);

		// Case: Different structures
		if ($memberNode->structureId !== $targetMemberNode->structureId)
		{
			$this->cacheManager->setData($cacheKey, Type\MemberSubordinateRelationType::RELATION_OTHER_STRUCTURE);

			return Type\MemberSubordinateRelationType::RELATION_OTHER_STRUCTURE;
		}

		$memberPriorityCalculationService = new Member\PriorityCalculationService();
		$roleCollection = $this->roleRepository->findByIds([...$member->roles, ...$targetMember->roles]);

		// Case: In same node
		if ($member->nodeId === $targetMember->nodeId)
		{
			$priorityDifference = $memberPriorityCalculationService->getMemberPriorityDifference(
				$member,
				$targetMember,
				$roleCollection,
			);
			if ($priorityDifference === null)
			{
				$this->cacheManager->setData($cacheKey, Type\MemberSubordinateRelationType::RELATION_OTHER);

				return Type\MemberSubordinateRelationType::RELATION_OTHER;
			}

			$resultRelation = match (true)
			{
				$priorityDifference > 0 => Type\MemberSubordinateRelationType::RELATION_HIGHER,
				$priorityDifference === 0 => Type\MemberSubordinateRelationType::RELATION_EQUAL,
				default => Type\MemberSubordinateRelationType::RELATION_LOWER,
			};

			$this->cacheManager->setData($cacheKey, $resultRelation);

			return $resultRelation;
		}

		$isMemberNodeAncestor = $this->nodeRepository->isAncestor($memberNode, $targetMemberNode);
		$isTargetMemberNodeAncestor = $this->nodeRepository->isAncestor($targetMemberNode, $memberNode);

		// Case: Different subtrees
		if (!$isMemberNodeAncestor && !$isTargetMemberNodeAncestor)
		{
			$this->cacheManager->setData($cacheKey, Type\MemberSubordinateRelationType::RELATION_OTHER);

			return Type\MemberSubordinateRelationType::RELATION_OTHER;
		}

		if ($isMemberNodeAncestor)
		{
			$memberPriority =
				$memberPriorityCalculationService->getMemberAffectingChildPriority($member, $roleCollection);
			$targetMemberPriority =
				$memberPriorityCalculationService->getMemberPriority($targetMember, $roleCollection);

			if (!$memberPriority)
			{
				$this->cacheManager->setData($cacheKey, Type\MemberSubordinateRelationType::RELATION_OTHER);

				return Type\MemberSubordinateRelationType::RELATION_OTHER;
			}

			if (!$targetMemberPriority)
			{
				$this->cacheManager->setData($cacheKey, Type\MemberSubordinateRelationType::RELATION_HIGHER);

				return Type\MemberSubordinateRelationType::RELATION_HIGHER;
			}

			$priorityDifference = $memberPriority - $targetMemberPriority;
			$resultRelation = match (true)
			{
				$priorityDifference >= 0 => Type\MemberSubordinateRelationType::RELATION_HIGHER,
				default => Type\MemberSubordinateRelationType::RELATION_OTHER,
			};
			$this->cacheManager->setData($cacheKey, $resultRelation);

			return $resultRelation;
		}

		if ($isTargetMemberNodeAncestor)
		{
			$targetMemberPriority =
				$memberPriorityCalculationService->getMemberAffectingChildPriority($targetMember, $roleCollection);
			$memberPriority = $memberPriorityCalculationService->getMemberPriority($member, $roleCollection);

			if (!$targetMemberPriority)
			{
				$this->cacheManager->setData($cacheKey, Type\MemberSubordinateRelationType::RELATION_OTHER);

				return Type\MemberSubordinateRelationType::RELATION_OTHER;
			}

			if (!$memberPriority)
			{
				$this->cacheManager->setData($cacheKey, Type\MemberSubordinateRelationType::RELATION_LOWER);

				return Type\MemberSubordinateRelationType::RELATION_LOWER;
			}

			$priorityDifference = $memberPriority - $targetMemberPriority;
			$resultRelation = match (true)
			{
				$priorityDifference <= 0 => Type\MemberSubordinateRelationType::RELATION_LOWER,
				default => Type\MemberSubordinateRelationType::RELATION_OTHER,
			};

			$this->cacheManager->setData($cacheKey, $resultRelation);

			return $resultRelation;
		}

		$this->cacheManager->setData($cacheKey, Type\MemberSubordinateRelationType::RELATION_OTHER);

		return Type\MemberSubordinateRelationType::RELATION_OTHER;
	}

	/**
	 * @param int $nodeId
	 * @param bool $withAllChildNodes
	 * @param bool $onlyActive *
	 *
	 * @return NodeMemberCollection
	 * @throws WrongStructureItemException
	 */
	public function getAllEmployees(
		int $nodeId,
		bool $withAllChildNodes = false,
		bool $onlyActive = true,
	): Item\Collection\NodeMemberCollection
	{
		$offset = 0;
		$limit = 1000;

		$nodeMemberCollection = new Item\Collection\NodeMemberCollection();
		while (($memberCollection = $this->nodeMemberRepository->findAllByNodeIdAndEntityType(
				nodeId: $nodeId,
				entityType: MemberEntityType::USER,
				withAllChildNodes: $withAllChildNodes,
				limit: $limit,
				offset: $offset,
				onlyActive: $onlyActive,
			))
			&& !$memberCollection->empty())
		{
			foreach ($memberCollection as $member)
			{
				$nodeMemberCollection->add($member);
			}

			$offset += $limit;
		}

		return $nodeMemberCollection;
	}

	public function getPagedEmployees(
		int $nodeId,
		bool $withAllChildNodes = false,
		int $offset = 0,
		int $limit = 500,
		bool $onlyActive = true,
	): Item\Collection\NodeMemberCollection
	{
		return $this->nodeMemberRepository->findAllByNodeIdAndEntityType(
			nodeId: $nodeId,
			entityType: MemberEntityType::USER,
			withAllChildNodes: $withAllChildNodes,
			limit: $limit,
			offset: $offset,
			onlyActive: $onlyActive,
		);
	}

	public function getDefaultHeadRoleEmployees(int $nodeId): Item\Collection\NodeMemberCollection
	{
		$headRole = null;
		static $departmentHeadRole = null;
		static $teamHeadRole = null;

		$node = $this->nodeRepository->getById($nodeId);
		if (!$node)
		{
			return new Item\Collection\NodeMemberCollection();
		}

		if ($node->type === NodeEntityType::DEPARTMENT)
		{
			if ($departmentHeadRole === null)
			{
				$departmentHeadRole = Container::getRoleRepository()->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['HEAD'])?->id;
			}
			$headRole = $departmentHeadRole;
		}
		elseif ($node->type === NodeEntityType::TEAM)
		{
			if ($teamHeadRole === null)
			{
				$teamHeadRole = Container::getRoleRepository()->findByXmlId(NodeMember::TEAM_ROLE_XML_ID['TEAM_HEAD'])?->id;
			}
			$headRole = $teamHeadRole;
		}

		if ($headRole === null)
		{
			return new Item\Collection\NodeMemberCollection();
		}

		return $this->nodeMemberRepository->findAllByRoleIdAndNodeId($headRole, $node->id);
	}

	/**
	 * @param NodeMember $nodeMember
	 * @param Item\Node $node
	 *
	 * @return NodeMember
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 * @throws UpdateFailedException
	 *
	 * @deprecated use InternalContainer::getNodeMemberService()->moveMember instead
	 */
	public function moveMember(Item\NodeMember $nodeMember, Item\Node $node): Item\NodeMember
	{
		return InternalContainer::getNodeMemberService()->moveMember($nodeMember, $node);
	}

	/**
	 * @param NodeMember $nodeMember
	 *
	 * @return NodeMember|null
	 * @throws ArgumentException
	 * @throws DeleteFailedException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws UpdateFailedException
	 * @throws WrongStructureItemException
	 */
	public function removeUserMemberFromDepartment(Item\NodeMember $nodeMember): ?Item\NodeMember
	{
		$result = $this->removeUserWithEventQueue($nodeMember);
		$this->nodeMemberRepository->sendEventQueue();

		return $result;
	}

	/**
	 * @param NodeMember $nodeMember
	 *
	 * @return NodeMember|null
	 * @throws DeleteFailedException
	 * @throws UpdateFailedException
	 * @throws WrongStructureItemException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	protected function removeUserWithEventQueue(Item\NodeMember $nodeMember): ?Item\NodeMember
	{
		$rootNode = StructureHelper::getRootStructureDepartment();

		if (
			$nodeMember->entityType !== MemberEntityType::USER
			|| !$rootNode
		)
		{
			return null;
		}

		$lockName = "remove_from_department_user_{$nodeMember->entityId}";
		$timeout = 10;
		$connection = Application::getInstance()->getConnection();

		if (!$connection->lock($lockName, $timeout))
		{
			throw (new DeleteFailedException(
				'You can\'t remove nodeMember now',
			));
		}

		$node = $this->nodeRepository->getById($nodeMember->nodeId);
		if ($node->type === NodeEntityType::TEAM)
		{
			$this->nodeMemberRepository->remove($nodeMember);
			$connection->unlock($lockName);

			return null;
		}

		$nodeMemberCollection = $this->nodeMemberRepository->findAllByEntityIdAndEntityTypeAndNodeType(
			entityId: $nodeMember->entityId,
			entityType: $nodeMember->entityType,
			nodeType: NodeEntityType::DEPARTMENT,
			limit: 2,
		);

		$departmentsCollectionCount = $nodeMemberCollection->count();
		if ($departmentsCollectionCount <= 1)
		{
			$connection->unlock($lockName);

			throw (new DeleteFailedException(Loc::getMessage('HUMANRESOURCES_NODE_MEMBER_SERVICE_CANT_REMOVE_USER_FROM_LAST_DEPARTMENT')));
		}

		$this->nodeMemberRepository->remove($nodeMember);
		$connection->unlock($lockName);

		return null;
	}

	/**
	 * @param Item\Node $node
	 * @param array $departmentUserIds
	 *
	 * @return Item\Collection\NodeMemberCollection
	 * @throws \Bitrix\HumanResources\Exception\CreationFailedException
	 * @throws \Bitrix\HumanResources\Exception\DeleteFailedException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\DuplicateEntryException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Exception
	 */
	public function saveUsersToDepartment(Item\Node $node, array $departmentUserIds = []): Item\Collection\NodeMemberCollection
	{
		$nodeMemberCollection = new Item\Collection\NodeMemberCollection();

		$oldMembersCollection =
			$this->nodeMemberRepository->findAllByNodeIdAndEntityType(
				$node->id,
				MemberEntityType::USER,
				false,
				0,
			);
		$newUserIdList = [];

		$nodeMemberCollectionToAdd = new Item\Collection\NodeMemberCollection();
		$nodeMemberCollectionToUpdate = new Item\Collection\NodeMemberCollection();
		foreach ($departmentUserIds as $roleXmlId => $userIds)
		{
			$role = $this->roleRepository->findByXmlId($roleXmlId);

			if (!$role)
			{
				continue;
			}
			$userIds = array_filter(array_map('intval', $userIds));
			$newUserIdList = array_merge($newUserIdList, $userIds);

			foreach ($userIds as $userId)
			{
				$userMember = $oldMembersCollection->getFirstByEntityId($userId);

				if ($userMember)
				{
					if (($userMember->roles[0] ?? 0) !== $role->id)
					{
						$updatedMember = $userMember;
						$updatedMember->role = $role->id;
						$nodeMemberCollectionToUpdate->add($updatedMember);
						$nodeMemberCollection->add($updatedMember);
					}

					continue;
				}

				$nodeMemberToAdd = new NodeMember(
					entityType: MemberEntityType::USER,
					entityId: $userId,
					nodeId: $node->id,
					active: true,
					role: $role->id,
				);
				$nodeMemberCollectionToAdd->add($nodeMemberToAdd);
				$nodeMemberCollection->add($nodeMemberToAdd);
			}
		}

		$nodeMemberCollectionToRemove = $oldMembersCollection->filter(
			static function (Item\NodeMember $nodeMember) use ($newUserIdList)
			{
				return !in_array($nodeMember->entityId, $newUserIdList, true);
			},
		);

		$this->nodeMemberRepository->createByCollection($nodeMemberCollectionToAdd);
		$this->nodeMemberRepository->updateByCollection($nodeMemberCollectionToUpdate);
		$movedToRootUserNodeMemberCollection =
			$this->removeUserMembersFromDepartmentByCollection($nodeMemberCollectionToRemove)
		;

		foreach ($movedToRootUserNodeMemberCollection as $nodeMember)
		{
			$nodeMemberCollection->add($nodeMember);
		}

		return $nodeMemberCollection;
	}

	/**
	 * @param NodeMemberCollection $nodeMemberCollection
	 *
	 * @return NodeMemberCollection
	 * @throws ArgumentException
	 * @throws DeleteFailedException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws UpdateFailedException
	 * @throws WrongStructureItemException
	 * @throws SqlQueryException
	 */
	public function removeUserMembersFromDepartmentByCollection(
		Item\Collection\NodeMemberCollection $nodeMemberCollection,
	): Item\Collection\NodeMemberCollection
	{
		$movedToRootUserNodeMemberCollection = new Item\Collection\NodeMemberCollection();
		$connection = Application::getConnection();
		try
		{
			$connection->startTransaction();
			foreach ($nodeMemberCollection as $nodeMember)
			{
				$movedToRootUserNodeMember = $this->removeUserWithEventQueue($nodeMember);
				if (!$movedToRootUserNodeMember)
				{
					continue;
				}
				$movedToRootUserNodeMemberCollection->add($movedToRootUserNodeMember);
			}
			$connection->commitTransaction();
			$this->nodeMemberRepository->sendEventQueue();
		}
		catch (\Exception $exception)
		{
			$connection->rollbackTransaction();
			$this->nodeMemberRepository->clearEventQueue();;
			throw $exception;
		}

		return $nodeMemberCollection;
	}

	/**
	 * @param Item\Node $node
	 * @param array{
	 *      MEMBER_HEAD?: list<int>,
	 *      MEMBER_EMPLOYEE?: list<int>,
	 *      MEMBER_DEPUTY_HEAD?: list<int>
	 * } $departmentUserIds
	 *
	 * @return bool
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\DuplicateEntryException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\HumanResources\Exception\CreationFailedException
	 */
	public function moveUsersToDepartment(Item\Node $node, array $departmentUserIds = []): Item\Collection\NodeMemberCollection
	{
		$nodeMemberCollection = new Item\Collection\NodeMemberCollection();
		$nodeMemberCollectionToUpdate = new Item\Collection\NodeMemberCollection();
		$nodeMemberCollectionToRemove = new Item\Collection\NodeMemberCollection();

		foreach ($departmentUserIds as $roleXmlId => $userIds)
		{
			$role = $this->roleRepository->findByXmlId($roleXmlId);

			if (!$role)
			{
				continue;
			}

			$userIds = array_filter(array_map('intval', $userIds));

			$userCollection = $this->nodeMemberRepository->findAllByEntityIdsAndEntityTypeAndNodeType(
				entityIds: $userIds,
				entityType: MemberEntityType::USER,
				nodeType: NodeEntityType::DEPARTMENT,
			);

			$userAlreadyBelongsToNode = [];
			$checkedUserIds = [];
			foreach ($userCollection as $userMember)
			{
				if (!in_array($userMember->entityId, $checkedUserIds, true))
				{
					if ($this->nodeMemberRepository->findByEntityTypeAndEntityIdAndNodeId(
						entityType: MemberEntityType::USER,
						entityId: $userMember->entityId,
						nodeId: $node->id,
					))
					{
						$userAlreadyBelongsToNode[] = $userMember->entityId;
					}
					$checkedUserIds[] = $userMember->entityId;
				}

				if (
					$nodeMemberCollectionToUpdate->getFirstByEntityId($userMember->entityId)
					|| (
						in_array($userMember->entityId, $userAlreadyBelongsToNode, true)
						&& $userMember->nodeId !== $node->id
					)
				)
				{
					$nodeMemberCollectionToRemove->add($userMember);

					continue;
				}

				$updatedUserMember = $userMember;
				$updatedUserMember->nodeId = $node->id;
				$updatedUserMember->role = $role->id;
				$nodeMemberCollectionToUpdate->add($updatedUserMember);
				$nodeMemberCollection->add($updatedUserMember);
			}
		}

		$this->nodeMemberRepository->removeByCollection($nodeMemberCollectionToRemove);
		$this->nodeMemberRepository->updateByCollection($nodeMemberCollectionToUpdate);

		return $nodeMemberCollection;
	}

	public function getNearestUserIdByEmployeeUserIdAndRole(int $employeeUserId, StructureRole $structureRole): int
	{
		$nodeMember = $this->getNodeMemberByEmployeeUserIdAndRoleId(
			$employeeUserId,
			$structureRole,
			DepthLevel::FULL,
		);

		return (int)$nodeMember?->entityId;
	}

	private function getNodeMemberByEmployeeUserIdAndRoleId(
		int $employeeUserId,
		StructureRole $structureRole,
		DepthLevel $depthLevel = DepthLevel::NONE,
	): ?NodeMember
	{
		if ($employeeUserId < 1)
		{
			return null;
		}

		$nodeFilter = new NodeFilter(
			entityTypeFilter: new NodeTypeFilter(
				new NodeEntityTypeCollection(NodeEntityType::DEPARTMENT)
			),
			direction: Direction::ROOT,
			depthLevel: $depthLevel,
		);

		return (new NodeMemberDataBuilder())
			->setFilter(new NodeMemberFilter(
				entityIdFilter: new EntityIdFilter(new Type\IntegerCollection($employeeUserId)),
				entityType: MemberEntityType::USER,
				nodeFilter: $nodeFilter,
				findRelatedMembers: true,
			))
			->addStructureRole($structureRole)
			->setSort(new NodeSort(depth: SortDirection::Desc))
			->get()
		;
	}
}