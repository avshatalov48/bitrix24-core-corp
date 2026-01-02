<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Provider\Params;

class UserParams
{
	public function __construct(
		public readonly int $userId,
		public readonly array $targetUserIds,
		public readonly bool $checkAccess = true,
		public readonly bool $withRights = true,
	)
	{
	}
}
