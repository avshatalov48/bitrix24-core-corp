<?php

namespace Bitrix\HumanResources\Service;

use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Type;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Type\MemberSubordinateRelationType;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Util\StructureHelper;
use Bitrix\HumanResources\Exception\DeleteFailedException;
use Bitrix\Main\Application;

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
	 * @throws \Bitrix\HumanResources\Exception\WrongStructureItemException
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
		static $headRole = null;

		if ($headRole === null)
		{
			$headRole = Container::getRoleRepository()->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['HEAD'])?->id;
		}

		if ($headRole === null)
		{
			return new Item\Collection\NodeMemberCollection();
		}

		return $this->nodeMemberRepository->findAllByRoleIdAndNodeId($headRole, $nodeId);
	}

	/**
	 * @throws UpdateFailedException
	 */
	public function moveMember(Item\NodeMember $nodeMember, Item\Node $node): Item\NodeMember
	{
		$nodeMember->nodeId = $node->id;
		$this->nodeMemberRepository->update($nodeMember);

		return $nodeMember;
	}

	/**
	 * @param NodeMember $nodeMember
	 *
	 * @return NodeMember|null
	 * @throws UpdateFailedException
	 * @throws \Bitrix\HumanResources\Exception\WrongStructureItemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function removeUserMemberFromDepartment(Item\NodeMember $nodeMember): ?Item\NodeMember
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
			return null;
		}

		$nodeMemberCollection = $this->nodeMemberRepository->findAllByEntityIdAndEntityTypeAndNodeType(
			entityId: $nodeMember->entityId,
			entityType: $nodeMember->entityType,
			nodeType: NodeEntityType::DEPARTMENT,
			limit: 2,
		);

		$departmentsCollectionCount = $nodeMemberCollection->count();
		if (
			$nodeMember->nodeId === $rootNode->id
			&& $departmentsCollectionCount <= 1
		)
		{
			$connection->unlock($lockName);

			return null;

		}

		if ($departmentsCollectionCount <= 1)
		{
			$nodeMember->role = $this->roleRepository->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['EMPLOYEE'])->id;
			$nodeMember->nodeId = $rootNode->id;
			$connection->unlock($lockName);

			return $this->nodeMemberRepository->update($nodeMember);
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
	 * @param Item\Collection\NodeMemberCollection $nodeMemberCollection
	 *
	 * @return array|Item\Collection\NodeMemberCollection
	 * @throws UpdateFailedException
	 * @throws \Bitrix\HumanResources\Exception\WrongStructureItemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
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
				$movedToRootUserNodeMember = $this->removeUserMemberFromDepartment($nodeMember);
				if (!$movedToRootUserNodeMember)
				{
					continue;
				}
				$movedToRootUserNodeMemberCollection->add($movedToRootUserNodeMember);
			}
			$connection->commitTransaction();
		}
		catch (\Exception $exception)
		{
			$connection->rollbackTransaction();
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
}