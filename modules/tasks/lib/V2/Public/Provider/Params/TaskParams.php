<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Provider\Params;

class TaskParams
{
	public function __construct(
		public readonly int $taskId,
		public readonly int $userId,
		public readonly bool $group = true,
		public readonly bool $flow = true,
		public readonly bool $stage = true,
		public readonly bool $members = true,
		public readonly bool $checkLists = true,
		public readonly bool $chat = true,
		public readonly bool $tags = true,
		public readonly bool $crm = true,
		public readonly bool $subTasks = true,
		public readonly bool $relatedTasks = true,
		public readonly bool $gantt = true,
		public readonly bool $favorite = true,
		public readonly bool $options = true,
		public readonly bool $parameters = true,
		public readonly bool $checkTaskAccess = true,
		public readonly bool $checkGroupAccess = true,
		public readonly bool $checkFlowAccess = true,
		public readonly bool $checkCrmAccess = true,
		public readonly bool $checkParentAccess = true,
	)
	{

	}
}
