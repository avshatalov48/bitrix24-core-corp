<?php

namespace Bitrix\Sign\Item\Hr;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Type\Hr\NodeSyncStatus;

final class MemberUser implements Contract\Item
{
	public function __construct(
		public readonly int $memberId,
		public readonly int $userId,
	)
	{
	}
}
