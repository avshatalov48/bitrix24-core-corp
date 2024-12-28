<?php

namespace Bitrix\HumanResources\Contract\Service;

use Bitrix\HumanResources\Enum\NodeActiveFilter;
use Bitrix\HumanResources\Exception\DeleteFailedException;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item\Collection\NodeCollection;
use Bitrix\HumanResources\Item\Node;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

interface NodeService
{
	/**
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws \Throwable
	 * @throws WrongStructureItemException
	 * @throws ArgumentException
	 * @throws SystemException
	 * @throws DeleteFailedException
	 */
	public function removeNode(Node $node): bool;

	/**
	 * @throws \Bitrix\HumanResources\Exception\CreationFailedException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function insertNode(Node $node, bool $move = true): Node;

	/**
	 * @param \Bitrix\HumanResources\Item\Node $node
	 *
	 * @return \Bitrix\HumanResources\Item\Node
	 */
	public function insertAndMoveNode(Node $node): Node;

	/**
	 * @param \Bitrix\HumanResources\Item\Node $node
	 * @param \Bitrix\HumanResources\Item\Node|null $targetNode
	 *
	 * @return \Bitrix\HumanResources\Item\Node
	 */
	public function moveNode(Node $node, ?Node $targetNode): Node;
	public function getNodesByUserId(int $userId, NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE): NodeCollection;
	public function getNodesByUserIdAndUserRoleId(int $userId, int $roleId, NodeActiveFilter $activeFilter = NodeActiveFilter::ONLY_GLOBAL_ACTIVE): NodeCollection;
	public function getNodeChildNodes(int $nodeId): NodeCollection;
	public function getNodeChildNodesByAccessCode(string $accessCode): NodeCollection;

	public function getNodeInformation(int $nodeId): ?Node;

	/**
	 * @throws ObjectPropertyException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function updateNode(Node $node): Node;
}