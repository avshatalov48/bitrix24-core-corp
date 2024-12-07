<?php

namespace Bitrix\Tasks\Flow\Internal\Link;

use Bitrix\Tasks\Flow\Internal\FlowTaskTable;

class FlowLink
{
	/**
	 * @throws \Exception
	 */
	public static function link(int $flowId, int $taskId): void
	{
		FlowTaskTable::add([
			'FLOW_ID' => $flowId,
			'TASK_ID' => $taskId,
		]);
	}

	public static function unlink(int $taskId): void
	{
		FlowTaskTable::deleteRelation($taskId);
	}
}