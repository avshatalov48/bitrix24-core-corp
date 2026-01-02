<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Entity\User\Profile;

use Bitrix\Main\Entity\EntityCollection;

class GratitudeBadgeCollection extends EntityCollection
{
	protected static function getEntityClass(): string
	{
		return GratitudeBadge::class;
	}

	public function getTotalCount(): int
	{
		$totalCount = 0;

		foreach ($this->items as $userProfileGratitude)
		{
			$totalCount += $userProfileGratitude->count;
		}

		return $totalCount;
	}
}
