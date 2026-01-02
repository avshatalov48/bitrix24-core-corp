<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;
use Bitrix\Tasks\V2\Internal\Entity;

class TaskMemberMapper
{
	public function mapToCollection(array $members): Entity\UserCollection
	{
		$entities = [];
		foreach ($members as $member)
		{
			$entities[] = $this->mapToEntity($member);
		}

		return new Entity\UserCollection(...$entities);
	}

	public function mapToEntity(array $member): Entity\User
	{
		return new Entity\User(
			id: (int)$member['USER_ID'],
			role: $member['TYPE'],
		);
	}
}