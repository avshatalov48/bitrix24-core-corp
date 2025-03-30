<?php

namespace Bitrix\HumanResources\Contract\Repository\HcmLink;

use Bitrix\HumanResources\Item\Collection\HcmLink\MappingEntityCollection;
use Bitrix\HumanResources\Item\Collection\UserCollection;

interface UserRepository
{
	public function getMappingEntityCollectionByUserIds(array $userIds, int $limit, int $offset, ?string $searchName = null): MappingEntityCollection;

	public function getUsersIdBySearch(string $searchName, array $excludeIds, int $limit = 10): UserCollection;
}