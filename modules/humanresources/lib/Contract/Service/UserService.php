<?php

namespace Bitrix\HumanResources\Contract\Service;

use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item\Collection\NodeMemberCollection;
use Bitrix\HumanResources\Item\Collection\UserCollection;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Item\User;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

interface UserService
{
	public function getUserById(int $userId): ?User;
	public function getUserName(User $user): string;

	/**
	 * @throws ArgumentException
	 * @throws WrongStructureItemException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getUserCollectionFromMemberCollection(NodeMemberCollection $nodeMemberCollection): UserCollection;
	public function getUserAvatar(User $user, int $size = 25): ?string;
	public function getUserUrl(User $user): string;

	/**
	 * Check if the user has a connection to any department node
	 * @param int $userId
	 *
	 * @return bool
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function isEmployee(int $userId): bool;

	/**
	 * Returns an array of users who are members of a department
	 * @param array<int> $userIds
	 *
	 * @return array<int>
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function filterEmployees(array $userIds): array;

	/**
	 * Returns an array of basic user information
	 * @param User $user
	 *
	 * @return array {
	 *     id: int,
	 *     name: string,
	 *     avatar: ?string,
	 *     url: string,
	 *     workPosition: ?string,
	 * }
	 */
	public function getBaseInformation(User $user): array;

	/**
	 * Returns an array of basic user information
	 *
	 * @param Node $node
	 * @param string $searchQuery
	 *
	 * @return array<int, array{
	 *     id: int,
	 *     name: string,
	 *     avatar: ?string,
	 *     url: string,
	 *     workPosition: ?string
	 * }>
	 */
	public function findByNodeAndSearchQuery(Node $node, string $searchQuery): array;
}