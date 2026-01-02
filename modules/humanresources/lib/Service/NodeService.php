<?php

namespace Bitrix\HumanResources\Service;

use Bitrix\HumanResources\Command\Structure\Node\NodeOrderCommand;
use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Enum\DepthLevel;
use Bitrix\HumanResources\Enum\Direction;
use Bitrix\HumanResources\Enum\NodeActiveFilter;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Item\Collection\NodeCollection;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class NodeService implements Contract\Service\NodeService
{
	private Contract\Repository\NodeRepository $nodeRepository;
	private Contract\Service\StructureWalkerService $structureWalkerService;

	public function __construct(
		?Contract\Repository\NodeRepository $nodeRepository = null,
		?Contract\Service\StructureWalkerService $structureWalkerService = null,
	)
	{
		$this->nodeRepository = $nodeRepository ?? Container::getNodeRepository();
		$this->structureWalkerService = $structureWalkerService ?? Container::getStructureWalkerService();
	}
	public function getNodesByUserId(int $userId, NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE): NodeCollection
	{
		return $this->nodeRepository->findAllByUserId($userId, $activeFilter);
	}

	public function getNodesByUserIdAndUserRoleId(int $userId, int $roleId, NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE): NodeCollection
	{
		return $this->nodeRepository->findAllByUserIdAndRoleId($userId, $roleId, $activeFilter);
	}

	public function getNodeChildNodes(int $nodeId): NodeCollection
	{
		$node = $this->nodeRepository->getById($nodeId);
		if (!$node)
		{
			return new NodeCollection();
		}

		return $this->nodeRepository->getChildOf($node, DepthLevel::FULL);
	}

	public function getNodeChildNodesByAccessCode(string $accessCode): NodeCollection
	{
		$node = $this->nodeRepository->getByAccessCode($accessCode);
		if (!$node)
		{
			return new NodeCollection();
		}

		return $this->getNodeChildNodes($node->id);
	}

	public function getNodeInformation(int $nodeId): ?Node
	{
		return $this->nodeRepository->getById($nodeId);
	}

	/**
	 * @throws \Bitrix\HumanResources\Exception\CreationFailedException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function insertNode(Node $node, bool $move = true): Node
	{
		if ($node->parentId)
		{
			$parentNode = $this->nodeRepository->getById($node->parentId);
			if (!$parentNode)
			{
				throw (new CreationFailedException())->addError(new Error("Parent node with id $node->parentId dont exist"));
			}

			if ($node->type === NodeEntityType::DEPARTMENT && $parentNode->type === NodeEntityType::TEAM)
			{
				throw (new CreationFailedException())->addError(new Error("The parent type does not match the type of the node being created"));
			}
		}

		if ($move)
		{
			return $this->insertAndMoveNode($node);
		}

		if (!$node->id)
		{
			$this->nodeRepository->create($node);
		}

		return $node;
	}

	/**
	 * @param \Bitrix\HumanResources\Item\Node $node
	 * @param \Bitrix\HumanResources\Item\Node|null $targetNode
	 *
	 * @return \Bitrix\HumanResources\Item\Node
	 */
	public function moveNode(Node $node, ?Node $targetNode = null): Node
	{
		$direction = $targetNode !== null
			? Direction::CHILD
			: Direction::ROOT
		;

		$lastSibling = null;

		if ($targetNode)
		{
			$lastSibling = $this->nodeRepository->getChildOf($targetNode)->getLast();
		}

		$lastSiblingSort = $lastSibling ? $lastSibling->sort : 0;

		$node = $this->structureWalkerService->moveNode($direction, $node, $targetNode);

		$node->sort = $lastSiblingSort + NodeOrderCommand::ORDER_STEP;

		return $this->nodeRepository->update($node);
	}

	public function removeNode(Node $node): bool
	{
		try
		{
			$this->structureWalkerService->removeNode($node);
		}
		catch (\Throwable)
		{
			return false;
		}

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function insertAndMoveNode(Node $node): Node
	{
		$this->insertNode($node, false);

		$targetNode = null;
		if ($node->parentId)
		{
			$targetNode = $this->nodeRepository->getById($node->parentId);
		}

		return $this->moveNode($node, $targetNode);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws ArgumentException
	 * @throws SystemException|UpdateFailedException
	 */
	public function updateNode(Node $node): Node
	{
		$nodeEntity = $this->nodeRepository->getById($node->id);

		if (!$nodeEntity)
		{
			return $node;
		}

		if (
			$node->parentId !== $nodeEntity->parentId
		)
		{
			if ($node->parentId === $node->id)
			{
				throw (new UpdateFailedException())->addError(new Error("Node can't be its own parent"));
			}

			$targetNode = $this->nodeRepository->getById($node->parentId);
			if (!$targetNode)
			{
				throw (new UpdateFailedException())->addError(new Error("Parent node with id $node->parentId dont exist"));
			}

			$isAncestor = $this->nodeRepository->isAncestor($node, $targetNode);
			if ($isAncestor)
			{
				throw (new UpdateFailedException())->addError(new Error("Child node with id $node->id cannot become the parent of its own parent node with id $node->parentId"));
			}

			$this->moveNode($nodeEntity, $targetNode);
		}

		if ($node->sort !== $nodeEntity->sort)
		{
			$node->sort = $nodeEntity->sort;
		}

		return $this->nodeRepository->update($node);
	}
}