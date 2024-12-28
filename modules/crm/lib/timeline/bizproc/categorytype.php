<?php

namespace Bitrix\Crm\Timeline\Bizproc;

final class CategoryType
{
	public const WORKFLOW_STARTED = 1;
	public const WORKFLOW_COMPLETED = 2;
	public const WORKFLOW_TERMINATED = 3;
	public const TASK_ADDED = 4;
	public const TASK_COMPLETED = 5;
	public const TASK_DELEGATED = 6;
	public const COMMENT_ADDED = 7;
	public const COMMENT_READ = 8;
}
