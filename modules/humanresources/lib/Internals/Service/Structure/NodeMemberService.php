<?php

namespace Bitrix\HumanResources\Internals\Service\Structure;

use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Repository\NodeMemberRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class NodeMemberService
{
	private NodeMemberRepository $nodeMemberRepository;

	public function __construct()
	{
		$this->nodeMemberRepository = Container::getNodeMemberRepository();
	}

	/**
	 * @param Item\NodeMember $nodeMember
	 * @param Item\Node $node
	 * @param Item\Role|null $role
	 *
	 * @return Item\NodeMember
	 * @throws UpdateFailedException
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function moveMember(Item\NodeMember $nodeMember, Item\Node $node, ?Item\Role $role = null): Item\NodeMember
	{
		$nodeMember = $this->nodeMemberRepository->findById((int)$nodeMember->id);
		if (!$nodeMember)
		{
			throw (new UpdateFailedException(
				'Node member not found',
			));
		}

		if ($nodeMember->node?->type !== $node->type)
		{
			throw (new UpdateFailedException(
				'Wrong target node type',
			));
		}

		if ($role)
		{
			if (!$this->isRoleCorrectForNode($node, $role))
			{
				throw (new UpdateFailedException(
					'Wrong role for target node type',
				));
			}

			$nodeMember->role = $role->id;
		}

		$nodeMember->nodeId = $node->id;
		$this->nodeMemberRepository->update($nodeMember);

		return $nodeMember;
	}

	public function isUserInMultipleNodes(int $userId, NodeEntityType $nodeEntityType = NodeEntityType::DEPARTMENT): bool
	{
		$nodeMemberCollection = $this->nodeMemberRepository->findAllByEntityIdAndEntityTypeAndNodeType(
			entityId: $userId,
			entityType: MemberEntityType::USER,
			nodeType: NodeEntityType::DEPARTMENT,
			limit: 2,
		);

		return $nodeMemberCollection->count() > 1;
	}

	private function isRoleCorrectForNode(Item\Node $node, Item\Role $role): bool
	{
		if ($node->type === NodeEntityType::DEPARTMENT)
		{
			return in_array($role->xmlId, NodeMember::DEFAULT_ROLE_XML_ID, true);
		}

		if ($node->type === NodeEntityType::TEAM)
		{
			return in_array($role->xmlId, NodeMember::TEAM_ROLE_XML_ID, true);
		}

		return false;
	}
}