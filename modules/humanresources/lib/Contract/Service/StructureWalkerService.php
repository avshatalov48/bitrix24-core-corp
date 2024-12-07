<?php

namespace Bitrix\HumanResources\Contract\Service;

use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Enum\Direction;
use Bitrix\Main;

interface StructureWalkerService
{
	public function moveNode(
		Direction $direction,
		Node $node,
		?Node $targetNode = null,
	): Node;

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
	public function removeNode(
		Node $node,
	): void;

	/**
	 * @param int $structureId
	 *
	 * @return Main\Result
	 */
	public function rebuildStructure(int $structureId): Main\Result;
}