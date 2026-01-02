<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Public\Service\Team;

use Bitrix\HumanResources\Builder\Structure\Filter\Column\EntityIdFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\IdFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\Node\NodeTypeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\NodeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\NodeMemberFilter;
use Bitrix\HumanResources\Builder\Structure\NodeDataBuilder;
use Bitrix\HumanResources\Builder\Structure\NodeMemberDataBuilder;
use Bitrix\HumanResources\Enum\DepthLevel;
use Bitrix\HumanResources\Enum\Direction;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item\Collection\NodeCollection;
use Bitrix\HumanResources\Public\Service\Container as PublicContainer;
use Bitrix\HumanResources\Public\Service\Node\UserService as NodeUserService;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Type\StructureRole;

class UserService
{
	private NodeUserService $nodeUserService;

	public function __construct()
	{
		$this->nodeUserService = PublicContainer::getUserService();
	}

	/**
	 * @param int $userId
	 * @param Direction $orderDirection
	 *
	 * @return list<NodeCollection>
	 * @throws WrongStructureItemException
	 */
	public function getTeamChainsByUserId(int $userId, Direction $orderDirection = Direction::ROOT): array
	{
		if ($userId <= 0)
		{
			return [];
		}

		$nodeDataBuilder = new NodeMemberDataBuilder();
		$nodeFilter = new NodeFilter(
			entityTypeFilter: NodeTypeFilter::fromNodeType(NodeEntityType::TEAM),
		);
		$filter =
			new NodeMemberFilter(
				entityIdFilter: EntityIdFilter::fromEntityId($userId),
				nodeFilter: $nodeFilter,
				findRelatedMembers: false,
			);

		$currentTeamMembers = $nodeDataBuilder
			->setFilter($filter)
			->getAll();

		if (empty($currentTeamMembers->getNodeIds()))
		{
			return [];
		}

		$nodeFilter = new NodeFilter(
			idFilter: idFilter::fromIds($currentTeamMembers->getNodeIds()),
			entityTypeFilter: NodeTypeFilter::fromNodeType(NodeEntityType::TEAM),
			direction: Direction::ROOT,
			depthLevel: DepthLevel::FULL,
		);
		$fullNodeCollection = (new NodeDataBuilder())->setFilter($nodeFilter)->getAll()->orderMapByInclude();

		$result = [];
		foreach ($fullNodeCollection as $node)
		{
			if (!in_array($node->id, $currentTeamMembers->getNodeIds(), true))
			{
				continue;
			}

			$chain = [];
			$currentNode = $node;
			while ($currentNode)
			{
				array_unshift($chain, $currentNode);
				$currentNode = $fullNodeCollection->getItemById($currentNode->parentId) ?? null;
			}

			if ($orderDirection === Direction::ROOT)
			{
				$chain = array_reverse($chain, true);
			}

			$result[] = new NodeCollection(...$chain);
		}

		return $result;
	}

	/**
	 * Returns true if user is head of any team
	 *
	 * @param int $userId
	 *
	 * @return bool
	 */
	public function isHeadOfTeam(int $userId): bool
	{
		$headMember = $this->nodeUserService->findByUserIdAndStructureRoles(
			$userId,
			[StructureRole::TEAM_HEAD],
		);

		return $headMember !== null;
	}

	/**
	 * Returns true if user is head or deputy of any team
	 *
	 * @param int $userId
	 *
	 * @return bool
	 */
	public function isHeadOrDeputyOfTeam(int $userId): bool
	{
		$headMember = $this->nodeUserService->findByUserIdAndStructureRoles(
			$userId,
			[StructureRole::TEAM_HEAD, StructureRole::TEAM_DEPUTY_HEAD],
		);

		return $headMember !== null;
	}
}
