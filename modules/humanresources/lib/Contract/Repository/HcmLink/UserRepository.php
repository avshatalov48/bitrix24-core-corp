<?php

namespace Bitrix\HumanResources\Contract\Repository\HcmLink;

use Bitrix\HumanResources\Item\Collection\HcmLink\MappingEntityCollection;

interface UserRepository
{
	public function getMappingEntityCollectionByUserIds(array $userIds, int $limit, int $offset): MappingEntityCollection;
}