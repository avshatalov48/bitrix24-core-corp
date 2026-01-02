<?php

namespace Bitrix\HumanResources\Service\Member;

use Bitrix\HumanResources\Enum\DepthLevel;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item\Collection\NodeCollection;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Repository;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class DepartmentUserSearchService
{
	private readonly Contract\Repository\NodeMemberRepository $nodeMemberRepository;
	private readonly Contract\Service\RoleHelperService $roleHelperService;
	private readonly Contract\Repository\NodeRepository $nodeRepository;
	private readonly Repository\NodePathRepository $nodePathRepository;

	public function __construct(
		?Contract\Repository\NodeMemberRepository $nodeMemberRepository = null,
		?Contract\Service\RoleHelperService $roleHelperService = null,
		?Contract\Repository\NodeRepository $nodeRepository = null,
	)
	{
		$this->nodeMemberRepository = $nodeMemberRepository ?? Container::getNodeMemberRepository();
		$this->roleHelperService = $roleHelperService ?? Container::getRoleHelperService();
		$this->nodeRepository = $nodeRepository ?? Container::getNodeRepository();
		$this->nodePathRepository = Container::getNodePathRepository();
	}

	/**
	 * @param int $departmentId
	 *
	 * @return NodeMember|null
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function findNearestHeadByDepartmentId(int $departmentId): ?Item\NodeMember
	{
		$headRoleId = $this->roleHelperService->getHeadRoleId();
		if (!$headRoleId)
		{
			return null;
		}

		$node = $this->nodeRepository->getById($departmentId);
		if (!$node || $node->type !== NodeEntityType::DEPARTMENT)
		{
			return null;
		}

		$nodeCollection = $this->nodeRepository->getParentOf($node, DepthLevel::FULL);
		foreach ($nodeCollection as $node)
		{
			$heads = $this->nodeMemberRepository->findAllByRoleIdAndNodeId($headRoleId, $node->id);
			if (!$heads->empty())
			{
				return $heads->getFirst();
			}
		}

		return null;
	}

	/**
	 * @param int $userId
	 *
	 * @return NodeMember|null
	 * @throws WrongStructureItemException
	 */
	public function findNearestHeadByUserId(int $userId): ?Item\NodeMember
	{
		$headRoleId = $this->roleHelperService->getHeadRoleId();
		if (!$headRoleId)
		{
			return null;
		}

		$nodeMembers = $this->nodeMemberRepository->findAllByEntityIdAndEntityType($userId, MemberEntityType::USER);
		if ($nodeMembers->empty())
		{
			return null;
		}

		$nodeCollection = $this->getFirstUserNodesWithPotentialHeads($userId);
		$checkNodeCollection = new NodeCollection();
		foreach ($nodeCollection as $node)
		{
			$isAncestor = false;
			foreach ($nodeCollection as $otherNode)
			{
				if ($node !== $otherNode && $this->nodeRepository->isAncestor($node, $otherNode))
				{
					$isAncestor = true;

					break;
				}
			}

			if (!$isAncestor)
			{
				$checkNodeCollection->add($node);
			}
		}

		foreach ($checkNodeCollection as $node)
		{
			$branchNodeCollection = $this->nodeRepository->getParentOf($node, DepthLevel::FULL);
			foreach ($branchNodeCollection as $branchNode)
			{
				$heads = $this->nodeMemberRepository->findAllByRoleIdAndNodeId($headRoleId, $branchNode->id);
				if (!$heads->empty())
				{
					return $heads->getFirst();
				}
			}
		}

		return null;
	}

	private function getFirstUserNodesWithPotentialHeads(int $userId): NodeCollection
	{
		$nodeMembers = $this->nodeMemberRepository->findAllByEntityIdAndEntityType($userId, MemberEntityType::USER);
		$headRoleId = $this->roleHelperService->getHeadRoleId();
		$nodeCollection = new NodeCollection();
		foreach ($nodeMembers as $nodeMember)
		{
			$node = $this->nodeRepository->getById($nodeMember->nodeId, true);
			if ($node && $node->type === NodeEntityType::DEPARTMENT)
			{
				if ((int)$nodeMember->roles[0] !== $headRoleId)
				{
					$nodeCollection->add($node);

					continue;
				}

				if ($node->parentId)
				{
					$node = $this->nodeRepository->getById($node->parentId, true);
					$nodeCollection->add($node);
				}
			}
		}

		return $nodeCollection;
	}

	/**
	 * @param int $departmentId
	 * @param array<int> $userIds
	 *
	 * @return NodeMember|null
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function findNearestFromUserListByDepartmentId(int $departmentId, array $userIds): ?Item\NodeMember
	{
		if (empty($userIds))
		{
			return null;
		}

		$node = $this->nodeRepository->getById($departmentId, true);
		if (!$node || $node->type !== NodeEntityType::DEPARTMENT)
		{
			return null;
		}

		$nodeMembers = $this->nodeMemberRepository->findAllByEntityIdsAndEntityType($userIds, MemberEntityType::USER);
		$departmentMembers = [];
		foreach ($nodeMembers as $nodeMember)
		{
			if ($nodeMember->nodeId === $node->id)
			{
				return $nodeMember;
			}

			if (!isset($departmentMembers[$nodeMember->nodeId]))
			{
				$departmentMembers[$nodeMember->nodeId] = $nodeMember;
			}
		}

		$nearestNodeId = $this->nodePathRepository->getNearestParentDepartmentIdByDepartmentList($node->id, array_keys($departmentMembers));

		return $nearestNodeId ? $departmentMembers[$nearestNodeId] : null;
	}

	/**
	 * @param int $userId
	 * @param array<int> $searchedUserIds
	 *
	 * @return NodeMember|null
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 *
	 * Finds nearest user nodeMember from a specified list of userIds within the department branch of the user
	 */
	public function findNearestFromUserListByUserId(int $userId, array $searchedUserIds): ?Item\NodeMember
	{
		if (empty($searchedUserIds))
		{
			return null;
		}

		$nodeCollection = $this->nodeRepository->findAllByUserId($userId);
		if ($nodeCollection->empty())
		{
			return null;
		}

		if ($nodeCollection->count() === 1)
		{
			return $this->findNearestFromUserListByDepartmentId($nodeCollection->getFirst()->id, $searchedUserIds);
		}

		$minDepth =  null;
		$nearestMember = null;
		foreach ($nodeCollection as $node)
		{
			$nodeMember = $this->findNearestFromUserListByDepartmentId($node->id, $searchedUserIds);
			if (!$nodeMember)
			{
				continue;
			}

			$node = $this->nodeRepository->getById($node->id, true);
			$memberNode = $this->nodeRepository->getById($nodeMember->nodeId, true);
			$depth = $node->depth - $memberNode->depth;
			if ($depth === 0)
			{
				return $nodeMember;
			}

			if ($depth < $minDepth || is_null($minDepth))
			{
				$minDepth = $depth;
				$nearestMember = $nodeMember;
			}
		}

		return $nearestMember;
	}
}