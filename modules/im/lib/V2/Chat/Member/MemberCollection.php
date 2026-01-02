<?php

declare(strict_types=1);

namespace Bitrix\Im\V2\Chat\Member;

use Bitrix\Im\V2\Chat\Member\Provider\MemberProvider;
use Bitrix\Im\V2\Entity\EntityCollection;

/**
 * @extends EntityCollection<MemberItem>
 */
class MemberCollection extends EntityCollection
{
	public static function getRestEntityName(): string
	{
		return 'members';
	}

	public static function initByRawResult(array $rawResult): static
	{
		$collection = new static();

		foreach ($rawResult as $row)
		{
			$collection[] = new MemberItem((int)$row['ID'], (int)$row['USER_ID'], $row['ROLE']);
		}

		return $collection;
	}

	public function getNextCursor(int $limit): ?MemberCursor
	{
		if ($this->count() < $limit)
		{
			return null;
		}

		$maxRole = null;
		$maxRolePriority = 0;
		$maxId = 0;

		foreach ($this as $item)
		{
			$rolePriority = MemberProvider::ROLE_PRIORITY_MAP[$item->getRole()];
			if ($rolePriority > $maxRolePriority)
			{
				$maxRole = $item->getRole();
				$maxRolePriority = $rolePriority;
				$maxId = $item->getId();
			}
			if ($item->getId() > $maxId)
			{
				$maxId = $item->getId();
			}
		}

		if ($maxRole === null || $maxId === null)
		{
			return null;
		}

		return new MemberCursor($maxRole, $maxId);
	}
}
