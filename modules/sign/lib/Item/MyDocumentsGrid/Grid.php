<?php

namespace Bitrix\Sign\Item\MyDocumentsGrid;

use Bitrix\Sign\Contract;

class Grid implements Contract\Item
{
	public function __construct(
		public RowCollection $rows,
		public int $totalCountMembers,
		public array $userIds,
	)
	{}
}