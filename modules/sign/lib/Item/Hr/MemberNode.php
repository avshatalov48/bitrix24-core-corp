<?php

namespace Bitrix\Sign\Item\Hr;

use Bitrix\Sign\Contract;

final class MemberNode implements Contract\Item
{
	public function __construct(
		public readonly int $documentId,
		public readonly int $memberId,
		public readonly int $nodeSyncId,
		public readonly int $userId,
	)
	{
	}
}
