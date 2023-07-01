<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2022 Bitrix
 */

namespace Bitrix\Tasks\Integration\Recyclebin;

class TasksMaxToRemoveFromRecycleBin
{
	private int $limit;

	public function __construct(int $limit = 100)
	{
		$this->limit = (int)\COption::GetOptionString('tasks', 'task_recyclebin_max_remove_limit', $limit);
	}

	public function getValue(): int
	{
		return $this->limit;
	}
}