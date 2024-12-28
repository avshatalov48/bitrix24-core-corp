<?php

namespace Bitrix\HumanResources\Contract\Repository;

use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item\Collection\NodeMemberCollection;
use Bitrix\HumanResources\Item\Collection\UserCollection;
use Bitrix\HumanResources\Item\Node;
use Bitrix\Humanresources\Item\User;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

interface UserRepository
{
	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getById(int $userId): ?User;

	/**
	 * @throws WrongStructureItemException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getUserCollectionByMemberCollection(NodeMemberCollection $nodeMemberCollection): UserCollection;

	public function getByIds(array $userIds);

	public function findByNodeAndSearchQuery(
		Node $node,
		string $searchQuery
	): UserCollection;
}
