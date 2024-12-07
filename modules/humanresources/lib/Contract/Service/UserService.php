<?php

namespace Bitrix\HumanResources\Contract\Service;

use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item\Collection\NodeMemberCollection;
use Bitrix\HumanResources\Item\Collection\UserCollection;
use Bitrix\HumanResources\Item\User;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CSite;
use CUser;

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
}