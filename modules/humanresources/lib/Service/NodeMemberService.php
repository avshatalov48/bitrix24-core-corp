<?php

namespace Bitrix\HumanResources\Service;

use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Type;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Type\MemberSubordinateRelationType;

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
				onlyActive: $onlyActive
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
			onlyActive: $onlyActive
		);
	}

	public function getDefaultHeadRoleEmployees(int $nodeId): Item\Collection\NodeMemberCollection
	{
		$headRole = Container::getRoleRepository()->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['HEAD'])?->id;

		if ($headRole === null)
		{
			return new Item\Collection\NodeMemberCollection();
		}

		return Container::getNodeMemberRepository()->findAllByRoleIdAndNodeId($headRole, $nodeId);
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
}