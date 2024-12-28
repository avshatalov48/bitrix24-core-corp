<?php

namespace Bitrix\HumanResources\Service;

use Bitrix\HumanResources\Enum\NodeActiveFilter;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\DeleteFailedException;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Model\NodePathTable;
use Bitrix\HumanResources\Model\NodeTable;
use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Contract\Repository\StructureRepository;
use Bitrix\HumanResources\Contract\Repository\NodeMemberRepository;
use Bitrix\HumanResources\Repository\RoleRepository;
use Bitrix\HumanResources\Enum\Direction;
use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Diag\FileLogger;
use Bitrix\Main\Diag\Logger;
use Bitrix\Main;
use Psr\Log\LogLevel;
use Throwable;

class StructureWalkerService implements Contract\Service\StructureWalkerService
{
	private ?Node $currentNode = null;
	private ?Node $targetNode = null;

	private NodeRepository $nodeRepository;
	private StructureRepository $structureRepository;
	private Connection $connection;
	private ?Logger $logger = null;

	private ?Direction $nodeDirection = null;
	private NodeMemberRepository $nodeMemberRepository;
	private Contract\Repository\RoleRepository $roleRepository;

	/**
	 * @param \Bitrix\HumanResources\Repository\NodeRepository|null $nodeRepository
	 */
	public function __construct(
		?NodeRepository $nodeRepository = null,
		?NodeMemberRepository $nodeMemberRepository = null,
		?RoleRepository $roleRepository = null,
		?StructureRepository $structureRepository = null,
	)
	{
		$this->nodeRepository = $nodeRepository ?? Container::getNodeRepository();
		$this->nodeMemberRepository = $nodeMemberRepository ?? Container::getNodeMemberRepository();
		$this->roleRepository = $roleRepository ?? Container::getRoleRepository();
		$this->structureRepository = $structureRepository ?? Container::getStructureRepository();

		$this->connection = Application::getConnection();

		if (defined('LOG_FILENAME'))
		{
			$this->logger = new FileLogger(LOG_FILENAME);
		}
	}

	/**
	 * @param Direction $direction
	 * @param \Bitrix\HumanResources\Item\Node $node
	 * @param \Bitrix\HumanResources\Item\Node|null $targetNode
	 *
	 * @return \Bitrix\HumanResources\Item\Node
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Throwable
	 */
	public function moveNode(
		Direction $direction,
		Node $node,
		?Node $targetNode = null,
	): Node
	{
		$this->currentNode = $node;
		$this->targetNode = $targetNode;

		$this->nodeDirection = $direction;

		$this->move();

		return $this->currentNode;
	}

	/**
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Throwable
	 */
	protected function move(): void
	{
		try
		{
			$this->connection->startTransaction();

			if (!$this->getParentId())
			{
				NodePathTable::createRootNode($this->currentNode->id);
				$this->connection->commitTransaction();
				return;
			}

			NodePathTable::moveWithSubtree($this->currentNode->id, $this->getParentId());

			$this->connection->commitTransaction();
			return;
		}
		catch (Throwable $exception)
		{
			$this->logger?->log(LogLevel::ERROR, $exception->getMessage());
			$this->connection->rollbackTransaction();
			throw $exception;
		}
	}

	private function getParentId(): ?int
	{
		return match ($this->nodeDirection)
		{
			Direction::ROOT => $this->getRootParentId(),
			Direction::CHILD => $this->targetNode->id,
		};
	}

	private function getRootParentId(): ?int
	{
		return $this->targetNode === $this->currentNode
			? null
			: $this->targetNode->parentId;
	}

	/**
	 * @param \Bitrix\HumanResources\Item\Node $node
	 *
	 * @throws \Bitrix\HumanResources\Exception\DeleteFailedException
	 * @throws \Bitrix\HumanResources\Exception\WrongStructureItemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Throwable
	 */
	public function removeNode(Node $node): void
	{
		if (!$node->parentId)
		{
			throw (new DeleteFailedException('You can\'t remove root node'));
		}

		try
		{
			$this->connection->startTransaction();

			$this->moveChildNodes($node);
			$this->moveMembers($node);

			$this->nodeRepository->deleteById($node->id);
			$this->connection->commitTransaction();

			return;
		}
		catch (Throwable $exception)
		{
			$this->logger?->log(LogLevel::ERROR, $exception->getMessage());
			$this->connection->rollbackTransaction();
			throw $exception;
		}
	}

	/**
	 * @param \Bitrix\HumanResources\Item\Node $node
	 *
	 * @return array<int>
	 * @throws \Bitrix\HumanResources\Exception\WrongStructureItemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Throwable
	 */
	private function moveChildNodes(Node $node): array
	{
		$children = $this->nodeRepository->getChildOf(
			node: $node,
			activeFilter: NodeActiveFilter::ALL,
		);
		$childIds = [];

		$parent = $this->nodeRepository->getById($node->parentId);

		foreach ($children as $child)
		{
			$childIds[] = $child->id;
			$this->moveNode(Direction::CHILD, $child, $parent);

			if ($child->parentId === $parent->id)
			{
				continue;
			}

			$child->parentId = $parent->id;
			$this->nodeRepository->update($child);
		}

		return $childIds;
	}

	/**
	 * @param \Bitrix\HumanResources\Item\Node $node
	 *
	 * @return void
	 */
	private function moveMembers(Node $node): void
	{
		$offset = 0;
		$limit = 1000;
		$roleEmployee = $this->roleRepository->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['EMPLOYEE']);

		while (($memberCollection = $this->nodeMemberRepository->findAllByNodeId(
				nodeId: $node->id,
				limit: $limit,
				offset: $offset,
		)) && !$memberCollection->empty())
		{
			foreach ($memberCollection as $member)
			{
				$member->nodeId = $node->parentId;
				$member->role = $roleEmployee->id;

				if ($this->nodeMemberRepository->findByEntityTypeAndEntityIdAndNodeId(
					entityType: $member->entityType,
					entityId: $member->entityId,
					nodeId: $member->nodeId,
				))
				{
					continue;
				}

				try
				{
					$this->nodeMemberRepository->create($member);
				}
				catch (Throwable)
				{
				}
			}
			$offset += $limit;
		}

		$this->nodeMemberRepository->removeAllMembersByNodeId($node->id);
	}

	/**
	 * @inheritDoc
	 */
	public function rebuildStructure(int $structureId): Main\Result
	{
		$result = new Main\Result();

		$structure = $this->structureRepository->getById($structureId);
		if (!$structure)
		{
			return $result->addError(
				new Main\Error("Structure with id: ${structureId} not found")
			);
		}

		try
		{
			$this->connection->startTransaction();
			NodePathTable::recalculate($structure->id);
			$this->connection->commitTransaction();
		}
		catch (\Throwable $throwable)
		{
			try
			{
				$this->connection->rollbackTransaction();
			}
			catch (SqlQueryException $e)
			{
			}

			return $result->addError(
				new Main\Error(
					message: $throwable->getMessage(),
				)
			);
		}

		return $result;
	}
}