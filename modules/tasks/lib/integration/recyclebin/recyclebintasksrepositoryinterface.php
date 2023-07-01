<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2022 Bitrix
 */

namespace Bitrix\Tasks\Integration\Recyclebin;

interface RecyclebinTasksRepositoryInterface
{
	public function removeTasksFromRecycleBin(
		TasksMaxDaysInRecycleBin $maxDaysTTL,
		TasksMaxToRemoveFromRecycleBin $maxTasksToRemove
	): void;
}